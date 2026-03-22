<?php

namespace Task5\Domain\Abstract;


abstract class Model
{

    private function normalizeArrayForComparison(array $array): array
    {
        $normalized = [];
        foreach ($array as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $normalized[$key] = $value->value;
            } else if ($value instanceof \UnitEnum) {
                $normalized[$key] = $value->name;
            } else {
                $normalized[$key] = $value;
            }
        }
        if (isset($array['id'])) unset($normalized['id']);
        if (isset($array['createdAt'])) unset($normalized['createdAt']);
        return $normalized;
    }

    private function checkRequiredData(array $data): bool
    {
        $requiredFields = get_class_vars(get_class($this))['required'] ?? [];
        if (empty($requiredFields)) return true;
        //delete empty fields
        foreach ($data as $key => $value) {
            if (strlen(trim($value)) === 0) {
                unset($data[$key]);
            }
        }
        $inputDataKeys = array_keys($data);
        $result = array_diff($requiredFields, $inputDataKeys);
        if (!empty($result)) {
            //var_dump('Required fields: ' . implode(', ', $result) . ' is empty');
            return false;
        }
        return true;
    }

    private function validateInputDataByInstance(array $data): array
    {
        $instanceFields = get_class_vars(get_class($this)) ?? [];
        $instanceArray = $instanceFields;
        $instanceFields = array_keys($instanceFields);

        $data = array_intersect_key($data, $instanceArray);

        if (empty($instanceFields)) return [];
        foreach ($instanceArray as $instanceKey => $instanceValue) {
            if ($instanceKey === 'tableName' || $instanceKey === 'id' || $instanceKey === 'createdAt' || $instanceKey === 'required') {
                continue;
            }
            if ($instanceValue !== NULL) {
                if (!isset($data[$instanceKey])) {
                    $data[$instanceKey] = $instanceValue;
                    continue;
                }
                if ($instanceValue instanceof \BackedEnum) {
                    $data[$instanceKey] = $instanceValue::tryFrom($data[$instanceKey])->value ?? $instanceValue->value;
                }
            }
        }
        return $data;
    }

    public function getAllWhere(string $hook, string|int $value): array
    {
        $tableName = get_class_vars(get_class($this))['tableName'] ?? null;
        if ($tableName === NULL) {
            //var_dump("Table name not defined");
            return [];
        }
        $result = file_get_contents(__DIR__ . '/../../' . $tableName . '.json');
        if ($result === false) {
            return [];
        }
        $result = json_decode($result, true);
        $sortedResult = [];
        foreach ($result as $row) {
            if ($row[$hook] === $value) {
                $sortedResult[] = $row;
            }
        }
        return $sortedResult;
    }

    public function getAll(string|null $limit = null, string|null $cursor = null): array
    {

        $tableName = get_class_vars(get_class($this))['tableName'] ?? null;
        if ($tableName === NULL) {
            //var_dump("Table name not defined");
            return [];
        }

        $result = file_get_contents(__DIR__ . '/../../' . $tableName . '.json');
        if ($result === false) {
            return [];
        }

        if ($limit !== null) {
            if (!is_numeric($limit)) {
                $limit = null;
            } else {
                $limit = (int)$limit;
                if ($limit < 1) $limit = 1;
                if ($limit > 100) $limit = 100;
            }
        }
        $result = json_decode($result, true);
        if (!is_array($result)) {
            return [];
        }
        $startIndex = 0;
        $cursorFounded = false;
        if ($cursor !== null) {
            foreach ($result as $key => $value) {
                if (isset($value['id']) && $value['id'] === $cursor) {
                    $startIndex = $key;
                    $cursorFounded = true;
                    break;
                }
            }
            if (!$cursorFounded) {
                return [];
            }
        }
        $items = array_slice($result, $startIndex, $limit);
        $nextCursor = null;
        if (!empty($items) && count($items) === $limit && $startIndex + $limit < count($result)) {
            $nextElem = $result[$startIndex + $limit] ?? null;

            if ($nextElem && isset($nextElem['id'])) {
                $nextCursor = $nextElem['id'];
            }
        }
        $response = [
            'items' => $items,
        ];
        if ($nextCursor !== null) {
            $response['nextCursor'] = $nextCursor;
        }
        return $response;
    }

    public function getById(string $id): array
    {
        $tableName = get_class_vars(get_class($this))['tableName'] ?? null;
        if ($tableName === NULL) {
            //var_dump("Table name not defined");
            return [];
        }
        $previousData = json_decode(file_get_contents(__DIR__ . '/../../' . $tableName . '.json'), true);
        if ($previousData === null) {
            return [];
        }
        foreach ($previousData as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }
        return [];
    }

    public function add(array $data): array
    {
        $tableName = get_class_vars(get_class($this))['tableName'] ?? null;
        if ($tableName === NULL) {
            //var_dump("Table name not defined");
            return [];
        }
        if (!$this->checkRequiredData($data)) {
            return [];
        }

        $previousData = json_decode(file_get_contents(__DIR__ . '/../../' . $tableName . '.json'), true);

        $result = $this->validateInputDataByInstance($data);


        $currentIdempotencyKey = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? null;

        $instanceFields = get_class_vars(get_class($this));

        if (array_key_exists('createdAt', $instanceFields)) {
            $result['createdAt'] = new \DateTimeImmutable()->format('c');
        }

        $newResultId = uniqid();

        $idempotencyKeysList = json_decode(file_get_contents(__DIR__ . '/../../idempotency.json') ?? [], true);

        if ($currentIdempotencyKey !== null) {
            $newIdempotencyKeyData = [
                'id' => $newResultId,
                'idempotency_key' => $currentIdempotencyKey,
            ];
            if (!empty($idempotencyKeysList)) {
                foreach ($idempotencyKeysList as $idempotencyKey => $idempotencyValue) {
                    if ($idempotencyValue['idempotency_key'] === $currentIdempotencyKey) {
                        $searchingEntity = $this->getById($idempotencyValue['id']);
                        $compareResult = array_intersect_key($searchingEntity, $result);
                        if ($this->normalizeArrayForComparison($compareResult) ==
                            $this->normalizeArrayForComparison($result)
                        ) {
                            return $searchingEntity;
                        } else if (empty($searchingEntity)) {
                            unset($idempotencyKeysList[$idempotencyKey]);
                            break;
                        } else {
                            header('Content-Type: application/json');
                            http_response_code(409);
                            exit(0);
                        }
                    }
                }
            }
            // not found
            $idempotencyKeysList[] = $newIdempotencyKeyData;
            file_put_contents(__DIR__ . '/../../idempotency.json', json_encode($idempotencyKeysList));
        }

        if (array_key_exists('id', $instanceFields)) {
            $result['id'] = $newResultId;
        }
        $previousData[] = $result;
        file_put_contents(__DIR__ . '/../../' . $tableName . '.json', json_encode($previousData, JSON_PRETTY_PRINT));
        return $result;
    }

    public function editById(string $id, array $data): array
    {
        $tableName = get_class_vars(get_class($this))['tableName'] ?? null;
        if ($tableName === NULL) {
            //var_dump("Table name not defined");
            return [];
        }
        $previousData = json_decode(file_get_contents(__DIR__ . '/../../' . $tableName . '.json'), true);

        $result = $this->validateInputDataByInstance($data);

        $taskFoundFlag = false;
        foreach ($previousData as &$row) {
            if ($row['id'] === $id) {
                $taskFoundFlag = true;
                $idValue = $row['id'];
                $row = array_merge($row, $result);
                $row['id'] = $idValue;
            }
        }
        if (!$taskFoundFlag) {
            return [];
        }
        unset($row);
        file_put_contents(__DIR__ . '/../../' . $tableName . '.json', json_encode($previousData, JSON_PRETTY_PRINT));
        return $result;
    }

    public function deleteById(string $id): bool
    {
        $tableName = get_class_vars(get_class($this))['tableName'] ?? null;
        if ($tableName === NULL) {
            //var_dump("Table name not defined");
            return false;
        }
        $previousData = json_decode(file_get_contents(__DIR__ . '/../../' . $tableName . '.json'), true);

        foreach ($previousData as $key => $value) {
            if ($value['id'] === $id) {
                unset($previousData[$key]);
                file_put_contents(__DIR__ . '/../../' . $tableName . '.json', json_encode($previousData, JSON_PRETTY_PRINT));
                return true;
            }
        }
        return false;
    }
}