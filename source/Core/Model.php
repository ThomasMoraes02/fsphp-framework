<?php

namespace Source\Core;

use PDO;
use PDOException;
use Source\Support\Message;

/**
 * FSPHP | Class Model Layer Supertype Pattern
 *
 * @author Robson V. Leite <cursos@upinside.com.br>
 * @package Source\Models
 */
abstract class Model
{
    /** @var object|null */
    protected $data;

    /** @var \PDOException|null */
    protected $fail;

    /** @var Message|null */
    protected $message;

    /** @var string */
    protected $query;

    /** @var string|array */
    protected $params;

    /** @var string */
    protected $order;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /** @var string $entity database table */
    protected $entity;

    /** @var array $protected no update or create */
    protected $protected;

    /** @var array $entity database table */
    protected $required;

    /**
     * Model constructor.
     * @param string $entity database table name
     * @param array $protected table protected columns
     * @param array $required table required columns
     */
    public function __construct(string $entity, array $protected, array $required)
    {
        $this->entity = $entity;
        $this->protected = array_merge($protected, ['created_at', "updated_at"]);
        $this->required = $required;

        $this->message = new Message();
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (empty($this->data)) {
            $this->data = new \stdClass();
        }

        $this->data->$name = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data->$name);
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        return ($this->data->$name ?? null);
    }

    /**
     * @return null|object
     */
    public function data(): ?object
    {
        return $this->data;
    }

    /**
     * @return \PDOException
     */
    public function fail(): ?\PDOException
    {
        return $this->fail;
    }

    /**
     * @return Message|null
     */
    public function message(): ?Message
    {
        return $this->message;
    }

    /**
     * @param string|null $terms
     * @param string|null $params
     * @param string $columns
     * @return Model|mixed
     */
    public function find(?string $terms = null, ?string $params = null, string $columns = "*")
    {
        if($terms) {
            $this->query = "SELECT {$columns} FROM " . $this->entity . " WHERE {$terms}";
            parse_str($params ?? "", $this->params);
            return $this;    
        }

        $this->query = "SELECT {$columns} FROM " . $this->entity;
        return $this;
    }

    public function order(string $columnOrder): Model
    {
        $this->order = " ORDER BY {$columnOrder}";
        return $this;
    }

    public function limit(int $limit): Model
    {
        $this->limit = " LIMIT {$limit}";
        return $this;
    }

    public function offset(int $offset): Model
    {
        $this->offset = " OFFSET {$offset}";
        return $this;
    }

    /**
     * @param int $id
     * @param string $columns
     * @return null|mixed|Model
     */
    public function findById(int $id, string $columns = "*"): ?Model
    {
        $find = $this->find("id = :id", "id={$id}", $columns);
        return $find->fetch();
    }

    /**
     * @param boolean $all
     * @return null|array|mixed|Model
     */
    public function fetch(bool $all = false)
    {
        try {
            $stmt = Connect::getInstance()->prepare($this->query.$this->order.$this->limit.$this->offset);
            $stmt->execute($this->params);

            if(!$stmt->rowCount()) {
                return null;
            }

            if($all) {
                return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
            }

            return $stmt->fetchObject(static::class);

        } catch(PDOException $e) {
            $this->fail = $e;
            return null;
        }
    }

    /**
     * @param string $key
     * @return integer
     */
    public function count(string $key = "id"): int
    {
        $stmt = Connect::getInstance()->prepare($this->query);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }


    /**
     * @param array $data
     * @return integer|null
     */
    protected function create(array $data): ?int
    {
        try {
            $columns = implode(", ", array_keys($data));
            $values = ":" . implode(", :", array_keys($data));

            $stmt = Connect::getInstance()->prepare("INSERT INTO ". $this->entity ." ({$columns}) VALUES ({$values})");
            $stmt->execute($this->filter($data));

            return Connect::getInstance()->lastInsertId();
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @param string $select
     * @param string|null|array $params
     * @return null|\PDOStatement
     */
    protected function read(string $select, string $params = null): ?\PDOStatement
    {
        try {
            $stmt = Connect::getInstance()->prepare($select);
            if ($params) {
                parse_str($params, $params);
                foreach ($params as $key => $value) {
                    if ($key == 'limit' || $key == 'offset') {
                        $stmt->bindValue(":{$key}", $value, \PDO::PARAM_INT);
                    } else {
                        $stmt->bindValue(":{$key}", $value, \PDO::PARAM_STR);
                    }
                }
            }

            $stmt->execute();
            return $stmt;
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }


    /**
     * @param array $data
     * @param string $terms
     * @param string $params
     * @return integer|null
     */
    protected function update(array $data, string $terms, string $params): ?int
    {
        try {
            $dateSet = [];
            foreach ($data as $bind => $value) {
                $dateSet[] = "{$bind} = :{$bind}";
            }
            $dateSet = implode(", ", $dateSet);
            parse_str($params, $params);

            $stmt = Connect::getInstance()->prepare("UPDATE ". $this->entity ." SET {$dateSet} WHERE {$terms}");
            $stmt->execute($this->filter(array_merge($data, $params)));
            return ($stmt->rowCount() ?? 1);
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @return boolean
     */
    public function save(): bool
    {
        if(!$this->required()) {
            $this->message->warning("Preencha todos os campos para continuar");
            return false;
        }

        // UPDATE
        if(!empty($this->id)) {
            $id = $this->id;
            $this->update($this->safe(), "id = :id", "id={$id}");
            if($this->fail()) {
                $this->message->error("Erro ao atualizar, verifique os dados");
                return false;
            }
        }

        // CREATE
        if(empty($this->id)) {
            $id = $this->create($this->safe());
            if($this->fail()) {
                $this->message->error("Erro ao atualizar, verifique os dados");
                return false;
            }
        }

        $this->data = $this->findById($id)->data();
        return true;
    }

    /**
     * Returns the last inserted id
     *
     * @return integer
     */
    public function lastId(): int
    {
        return Connect::getInstance()->query("SELECT MAX(id) as maxId FROM {$this->entity}")->fetch()->maxId + 1;
    }


    /**
     * @param string $terms
     * @param string|null $params
     * @return boolean
     */
    protected function delete(string $terms, ?string $params): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare("DELETE FROM ".$this->entity." WHERE {$terms}");

            if($params) {
                parse_str($params, $params);
                $stmt->execute((array) $params);
                return true;
            }

            $stmt->execute();
            return true;
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return false;
        }
    }

    /**
     * @return boolean
     */
    public function destroy(): bool
    {
        if(empty($this->id)) {
            return false;
        }

        $destroy = $this->delete("id = :id", "id={$this->id}");
        return $destroy;
    }

    /**
     * @return array|null
     */
    protected function safe(): ?array
    {
        $safe = (array)$this->data;
        foreach ($this->protected as $unset) {
            unset($safe[$unset]);
        }
        return $safe;
    }

    /**
     * @param array $data
     * @return array|null
     */
    private function filter(array $data): ?array
    {
        $filter = [];
        foreach ($data as $key => $value) {
            $filter[$key] = (is_null($value) ? null : filter_var($value, FILTER_DEFAULT));
        }
        return $filter;
    }

    /**
     * @return bool
     */
    protected function required(): bool
    {
        $data = (array)$this->data();
        foreach ($this->required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
}