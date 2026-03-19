<?php
    class MysqlPDO {
        public $isConnected;
        protected $connection;

        public function __construct() {
            $this->isConnected = true;
            try {
                $this->connection = new PDO("mysql:host=".getenv('APP_HOST').":3306;dbname=".getenv('APP_DB'), getenv('APP_USER'), getenv('APP_PASS'));
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //sets the default to return 'named' properties in array.
            } catch (PDOException $e) {
                $this->isConnected = false;
                throw new PDOException($e->getMessage());
            }
        }

        public function Close() {
            $this->connection = null;
            $this->isConnected = false;
        }

        public function Query($query, $params = [], $buffered = true) {
            if(!$buffered)
                $this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);
            if(is_array($params) && count($params) > 0) {
                try {
                    $stmt = $this->connection->prepare($query);
                    if($stmt->execute($params))
                        return $stmt;
                    throw new PDOException(print_r($stmt->errorInfo(),true));
                } catch (PDOException $e) {
                    throw new PDOException($e->getMessage());
                }
            }
            else {
                try {
                    $stmt = $this->connection->query($query);
                    if($stmt === false)
                        throw new PDOException(print_r($this->connection->errorInfo(),true));
                    return $stmt;
                } catch (PDOException $e) {
                    throw new PDOException($e->getMessage());
                }
            }
        }

        private function FetchObject($stmt) {
            return $stmt->fetchObject();
        }

        private function FetchRow($stmt) {
            return $stmt->fetch();
        }

        private function FetchColumn($stmt) {
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        private function FetchRowColumn($stmt) {
            return $stmt->fetchColumn();
        }

        private function FetchRowAssoc($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        private function FetchArray($stmt, $return_type = PDO::FETCH_ASSOC) {
            return $stmt->fetch($return_type);
        }

        private function FetchRowArray($stmt, $return_type = PDO::FETCH_ASSOC) {
            return $stmt->fetchAll($return_type);
        }

        public function NumRows($result) {
            return count($result);
        }

        public function AffectedRows($stmt) {
            return $stmt->rowCount();
        }

        public function InsertID() {
            return $this->connection->lastInsertId();
        }

        public function QFetchObject($query, $params = []) {
            try {
                return $this->FetchObject($this->Query($query, $params));
            } catch (PDOException $e) {
                error_log("QFetchObject : " . $query . " : " . print_r($params, true));
                throw new PDOException($e->getMessage());
            }
        }

        public function QFetchRowAssoc($query, $params = []) {
            try {
                return $this->FetchRowAssoc($this->Query($query, $params));
            } catch (PDOException $e) {
                error_log("QFetchRowAssoc : " . $query . " : " . print_r($params, true));
                throw new PDOException($e->getMessage());
            }
        }

        public function QFetchRow($query, $params = []) {
            try {
                return $this->FetchRow($this->Query($query, $params));
            } catch (PDOException $e) {
                error_log("QFetchRow : " . $query . " : " . print_r($params, true));
                throw new PDOException($e->getMessage());
            }
        }

        public function QFetchColumn($query, $params = []) {
            try {
                return $this->FetchColumn($this->Query($query, $params));
            } catch (PDOException $e) {
                error_log("QFetchColumn : " . $query . " : " . print_r($params, true));
                throw new PDOException($e->getMessage());
            }
        }

        public function QFetchRowColumn($query, $params = []) {
            try {
                return $this->FetchRowColumn($this->Query($query, $params));
            } catch (PDOException $e) {
                error_log("QFetchRowColumn : " . $query . " : " . print_r($params, true));
                throw new PDOException($e->getMessage());
            }
        }


        public function QFetchArray($query, $params = []) {
            try {
                return $this->FetchArray($this->Query($query, $params));
            } catch (PDOException $e) {
                error_log("QFetchArray : " . $query . " : " . print_r($params, true));
                throw new PDOException($e->getMessage());
            }
        }


        public function QFetchRowArray($query, $params = [], $return_type = PDO::FETCH_ASSOC) { // FetchRowArray wrapper
            try {
                return $this->FetchRowArray($this->Query($query, $params), $return_type);
            } catch (PDOException $e) {
                error_log("QFetchRowArray : " . $query . " : " . print_r($params, true));
                throw new PDOException($e->getMessage());
            }
        }

        public function GetTableFields($table) { // returns an array w/ the tables fields
            try {
                $stmt = $this->connection->prepare("DESCRIBE " . $table);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $fields = [];
                foreach($result as $column){
                    $fields[$column['Field']] = ['FType' => $column['Type'], 'FNull' => $column['Null']];
                }
                return array_change_key_case($fields, CASE_LOWER);
            }
            catch (PDOException $e) {
                error_log("GetTableFields : " . $table . " : ".$e->getMessage());
                return [];
            }
        }

        public function QueryInsert($table, $fields) { // builds and performes a SQL INSERT query based on the user data
            // first get the tables fields
            $names = [];
            $namesPrep = [];
            $fieldsPrep = [];

            if (is_array($fields)) {
                $table_fields = $this->GetTableFields($table);
                if (count($fields) == 0) return 0;

                $fields = array_change_key_case($fields, CASE_LOWER);

                // prepare field names and values
                foreach ($fields as $field => $value) {
                    // check for valid fields
                    if (array_key_exists($field, $table_fields)) {
                        $names[] = $field;
                        $namesPrep[] = ":$field";
                        if($value == NULL && $table_fields[$field]['FNull'] == 'NO') {
                            $value = "";
                        }
                        $fieldsPrep[$field] = $value;
                    }
                }
            }
            // build field names and values
            $names = implode(",",$names);
            $namesPrep = implode(",",$namesPrep);
            if(count($fieldsPrep) == 0) return 0;
            try {
                $this->Query("INSERT INTO $table($names) VALUES($namesPrep)", $fieldsPrep);
                return $this->InsertID();
            }
            catch (PDOException $e) {
                error_log("QueryInsert2 : " . $table . " : " . print_r($fields, true));
                throw new PDOException($e->getMessage());
            }
        }

        public function QueryUpdate($table, $fields, $where_clause) { // builds and performs a SQL UPDATE query based on the user data
            $pairs = [];
            $fieldsPrep = [];
            // first get the tables fields
            $table_fields = $this->GetTableFields($table);

            $fields = array_change_key_case($fields, CASE_LOWER);

            //error_log(implode(", ", array_keys($table_fields)));
            //error_log(implode(", ", array_keys($fields)));

            if(is_array($fields)) {
                // prepare query
                foreach ($fields as $field => $value) {
                    // check for valid fields
                    if (array_key_exists($field, $table_fields)) {
                        $pairs[] = "$field = :$field";
                        if($value == NULL && $table_fields[$field]['FNull'] == 'NO') {
                            $value = "";
                        }
                        $fieldsPrep[$field] = $value;
                    }
                }
            }

            if(is_array($where_clause)) {
                // prepare query
                $where_clause = array_change_key_case($where_clause, CASE_LOWER);
                $pairsWhere = [];
                foreach ($where_clause as $field => $value) {
                    // check for valid fields
                    if (array_key_exists($field, $table_fields)) {
                        $pairsWhere[] = "$field = :$field";
                        $fieldsPrep[$field] = $value;
                    }
                    else {
                        error_log("QueryUpdate2 : $field : " . $table);
                    }
                }
                $where_clause = implode(" and ", $pairsWhere);
            }
            // build and perform query
            if (count($pairs) == 0 || count($fieldsPrep) == 0) return 0;
            $pairs = implode(",",$pairs);
            try {
                return $this->AffectedRows($this->Query("UPDATE $table SET $pairs WHERE $where_clause", $fieldsPrep));
            }
            catch (PDOException $e) {
                error_log("QueryUpdate3 : " . $table . " : " . print_r($fields, true) . " : " . print_r($where_clause, true));
                throw new PDOException($e->getMessage());
            }
        }

        public function BeginTransaction() {
            return $this->connection->beginTransaction();
        }

        public function CommitTransaction() {
            return $this->connection->commit();
        }

        public function RollbackTransaction() {
            return $this->connection->rollBack();
        }

        private function PreparedQuery($query, $params = []) {
            try {
                $stmt = $this->connection->prepare($query);
                if($stmt->execute($params) === false)
                        throw new PDOException(print_r($this->connection->errorInfo(), true));
                return $stmt;
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage());
            }
        }

        public function escapeString($string) {
            return $this->connection->quote($string);
        }
    }
?>
