<?php

namespace Drupal\cloud_system;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;

class CloudSystemDatabase {

  /**
   * Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  public $database;

  /**
   * Constructs some object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Validate the record whether existed.
   *
   * @param string $table
   * @param array $conditions
   * @param null $condition
   *
   * @return bool
   * @throws \Exception
   */
  public function exist($table, $conditions = [], $condition = NULL) {
    if (empty($table) || empty($conditions)) {
      return FALSE;
    }

    $database = $this->database;
    // Validate the table if exists.
    if (!$database->schema()->tableExists($table)) {
      return FALSE;
    }

    try {
      $query = $database->select($table, 't');
      $query->fields('t');
      foreach ($conditions as $field => $value) {
        $logic = is_array($value) ? 'IN' : '=';
        $query->condition($field, $value, $logic);
      }

      if (!empty($condition)) {
        $query->condition($condition);
      }

      if ($database->schema()->fieldExists($table, 'state')) {
        $query->condition('t.state', -1, '<>');
      }

      $return = $query->execute()->fetchField();
    } catch (\Exception $e) {
      throw $e;
    }

    return $return;
  }

  /**
   * Load the data object by conditions array.
   *
   * @param string $table
   *   The table name.
   * @param array $conditions
   *   The conditions array.
   * @param array $fields
   *   The query fields.
   * @param boolean $active
   *   Active flag.
   * @param array $order_bys
   *   The order by array.
   *
   * @return object|boolean.
   * @throws \Exception
   */
  public function getRow($table, $conditions = [], $fields = [], $active = FALSE, $order_bys = []) {
    if (empty($table) || empty($conditions)) {
      return FALSE;
    }

    $database = $this->database;
    // Validate the table if exists.
    if (!$database->schema()->tableExists($table)) {
      return FALSE;
    }

    try {
      $query = $database->select($table, 't');
      if (!empty($fields)) {
        $query->fields('t', $fields);
      }
      else {
        $query->fields('t');
      }

      $or = new Condition('OR');
      $or_count = 0;
      foreach ($conditions as $field => $value) {
        $logic = is_array($value) ? 'IN' : '=';

        if ($field == 'db_or') {
          foreach ($value as $f => $v) {
            $or->condition($f, $v, $logic);
            $or_count++;
          }
        }
        else {
          $query->condition($field, $value, $logic);
        }
      }

      if ($or_count) {
        $query->condition($or);
      }

      if (!$active && $database->schema()->fieldExists($table, 'state')) {
        $query->condition('t.state', -1, '<>');
      }

      if (!empty($order_bys)) {
        foreach ($order_bys as $order_by) {
          if (is_array($order_by)) {
            foreach ($order_by as $key => $order_desc) {
              if ($database->schema()->fieldExists($table, $key)
                && in_array(strtoupper($order_desc), ['ASC', 'DESC'])) {
                $query->orderBy($key, $order_desc);
              }
            }
          }
        }
      }

      $return = $query->execute()->fetchObject();
    } catch (\Exception $e) {
      throw $e;
    }

    return $return;
  }

  /**
   * Load the assoc data by conditions array.
   *
   * @param string $table
   *   The table name.
   * @param array $conditions
   *   The conditions array.
   * @param array $fields
   *   The query fields.
   * @param boolean $active
   *   Active flag.
   * @param array $order_bys
   *   The order by array.
   *
   * @return object|boolean.
   * @throws \Exception
   */
  public function getAssoc($table, $conditions = [], $fields = [], $active = FALSE, $order_bys = []) {
    if (empty($table) || empty($conditions)) {
      return FALSE;
    }

    $database = $this->database;
    // Validate the table if exists.
    if (!$database->schema()->tableExists($table)) {
      return FALSE;
    }

    try {
      $query = $database->select($table, 't');
      if (!empty($fields)) {
        $query->fields('t', $fields);
      }
      else {
        $query->fields('t');
      }

      foreach ($conditions as $field => $value) {
        $logic = is_array($value) ? 'IN' : '=';
        $query->condition($field, $value, $logic);
      }

      if (!$active && $database->schema()->fieldExists($table, 'state')) {
        $query->condition('t.state', -1, '<>');
      }

      if (!empty($order_bys)) {
        foreach ($order_bys as $order_by) {
          if (is_array($order_by)) {
            foreach ($order_by as $key => $order_desc) {
              if ($database->schema()->fieldExists($table, $key)
                && in_array(strtoupper($order_desc), ['ASC', 'DESC'])) {
                $query->orderBy($key, $order_desc);
              }
            }
          }
        }
      }

      $return = $query->execute()->fetchAssoc();
    } catch (\Exception $e) {
      throw $e;
    }

    return $return;
  }

  /**
   * Load the data object by conditions array.
   *
   * @param string $table
   *   The table name.
   * @param array $conditions
   *   The conditions array.
   * @param array $fields
   *   The query fields.
   * @param boolean $active
   *   Active flag.
   *
   * @return object|boolean.
   * @throws \Exception
   */
  public function getAll($table, $conditions = [], $fields = [], $active = FALSE) {
    if (empty($table) || empty($conditions)) {
      return FALSE;
    }

    $database = $this->database;
    // Validate the table if exists.
    if (!$database->schema()->tableExists($table)) {
      return FALSE;
    }

    try {
      $query = $database->select($table, 't');
      if (!empty($fields)) {
        $query->fields('t', $fields);
      }
      else {
        $query->fields('t');
      }


      $or = new Condition('OR');
      $or_count = 0;
      foreach ($conditions as $field => $value) {
        $logic = is_array($value) ? 'IN' : '=';

        if ($field == 'db_or') {
          $or->condition($field, $value, $logic);
          $or_count++;
        }
        else {
          $query->condition($field, $value, $logic);
        }
      }

      if ($or_count) {
        $query->condition($or);
      }

      if (!$active && $database->schema()->fieldExists($table, 'state')) {
        $query->condition('t.state', -1, '<>');
      }
      $return = $query->execute()->fetchAll();
    } catch (\Exception $e) {
      throw $e;
    }

    return $return;
  }

  /**
   * Load the assoc data by conditions array.
   *
   * @param string $table
   *   The table name.
   * @param array $conditions
   *   The conditions array.
   * @param array $fields
   *   The query fields.
   * @param boolean $active
   *   Active flag.
   *
   * @return object|boolean.
   * @throws \Exception
   */
  public function getAllAssoc($table, $conditions = [], $fields = [], $active = FALSE) {
    if (empty($table) || empty($conditions)) {
      return FALSE;
    }

    $database = $this->database;
    // Validate the table if exists.
    if (!$database->schema()->tableExists($table)) {
      return FALSE;
    }

    try {
      $query = $database->select($table, 't');
      if (!empty($fields)) {
        $query->fields('t', $fields);
      }
      else {
        $query->fields('t');
      }

      foreach ($conditions as $field => $value) {
        $logic = is_array($value) ? 'IN' : '=';
        $query->condition($field, $value, $logic);
      }
      if (!$active && $database->schema()->fieldExists($table, 'state')) {
        $query->condition('t.state', -1, '<>');
      }
      $return = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
      throw $e;
    }

    return $return;
  }

  /**
   * Save data.
   *
   * @param string $table
   *   The table name.
   * @param array $params
   *   The update or insert data array.
   * @param array $primarys
   *   The primary key array.
   * @param boolean $active
   *   Active flag.
   *
   * @return object|boolean
   * @throws \Exception
   */
  public function save($table = '', $params = [], $primarys = [], $active = FALSE) {
    if (empty($table) || empty($params) || empty($primarys)) {
      return FALSE;
    }

    $database = $this->database;
    // Validate the table if exists.
    if (!$database->schema()->tableExists($table)) {
      return FALSE;
    }

    $checkData = $this->getRow($table, $primarys, [], $active);
    if (!empty($checkData)) {
      try {
        $query = $database->update($table);
        $query->fields($params);
        foreach ($primarys as $field => $value) {
          $logic = is_array($value) ? 'IN' : '=';
          $query->condition($field, $value, $logic);
        }
        $save = $query->execute();
        $return = $this->getRow($table, $primarys, [], $active);
      } catch (\Exception $e) {
        throw $e;
      }
    }
    else {
      try {
        $query = $database->insert($table);
        $query->fields(array_keys($params))->values(array_values($params));
        $id = $query->execute();
        $condition = [];
        foreach ($primarys as $field => $value) {
          $condition = [$field => !empty($value) ? $value : $id];
        }
        $return = $this->getRow($table, $condition, [], $active);
      } catch (\Exception $e) {
        throw $e;
      }
    }
    return $return;
  }

  /**
   * Delete the data by conditions
   *
   * @param string $table
   *   The table name.
   * @param array $conditions
   *   The conditions array.
   * @param boolean $is_soft_delete
   *   The flag if real delete the data.
   *   FALSE soft delete, just update the state field.
   *   TRUE hard delete, delete the date from the database.
   *
   * @return boolean
   * @throws \Exception
   */
  public function delete($table = '', $conditions = [], $is_soft_delete = FALSE) {
    if (empty($table) || empty($conditions)) {
      return FALSE;
    }

    $database = $this->database;
    // Validate the table if exists.
    if (!$database->schema()->tableExists($table)) {
      return FALSE;
    }

    $checkData = $this->getRow($table, $conditions, [], $is_soft_delete);
    if (!empty($checkData)) {
      try {
        $is_soft_delete = $is_soft_delete && $database->schema()
            ->fieldExists($table, 'state');
        if ($is_soft_delete) {
          $query = $database->update($table);
          $query->fields([
            'state' => -1,
          ]);
          foreach ($conditions as $field => $value) {
            $logic = is_array($value) ? 'IN' : '=';
            $query->condition($field, $value, $logic);
          }
          $return = $query->execute();
        }
        else {
          $query = $database->delete($table);
          foreach ($conditions as $field => $value) {
            $logic = is_array($value) ? 'IN' : '=';
            $query->condition($field, $value, $logic);
          }
          $return = $query->execute();
        }
      } catch (\Exception $e) {
        throw $e;
      }
      return $checkData;
    }
    return FALSE;
  }

}