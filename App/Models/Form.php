<?php

namespace App\Models;

use Core\Model;
use Core\Session;

class Form extends Model {

    public function createTable() {
        $formName = $_POST['form_name'];
        $tableName = $_POST['name'];
        $columns = $_POST['columns'];
    
        $form_id = $this->createForm($formName, $tableName);
        
        $fields = [];
        $fields[] = [
            'name' => 'id',
            'db_name' => 'id',
            'type' => 'INT',
            'length' => '11',
            'options' => ['primary','auto_increment'],
        ];


        foreach ($columns as $column) {
            $fields[] = [
                'name' => $column['name'],
                'db_name' => $column['db_name'],
                'type' => $column['type'],
                'length' => !empty($column['length']) ?  $column['length'] : '',
                'options' => isset($column['options']) ? $column['options'] : []
            ];
        }
    
        
        $rows = [];
        foreach ($fields as $field) {
            $options = [];
            
            if (in_array('primary', $field['options'])) {
                $options[] = 'PRIMARY KEY';
            }
            if (in_array('auto_increment', $field['options'])) {
                $options[] = 'AUTO_INCREMENT';
            }

            if (in_array('null', $field['options'])) {
                $options[] = 'NULL';
            } else {
                $options[] = 'NOT NULL';
            }
            
            if(!empty($field['length'])) {
                $length = '( '.$field['length'].' )';
            } else {
                $length = '';
            }

            $optionString = implode(' ', $options);
            $rows[] = "{$field['db_name']} {$field['type']} $length $optionString";
            $this->createField($field['name'], $field['db_name'], $field['type'], $field['length'], implode(",",$field['options']), $form_id);
        }
    
        $rowsString = implode(', ', $rows);
        $sql = "CREATE TABLE $tableName ($rowsString)";
        echo $sql;
    
        $newTable = $this->db->insert($sql);
    }
    public function editTable($form_id) {

        $remainingColumns[] = 'id';

        $formName = $_POST['form_name'];
        $tableName = $_POST['name'];
        $columns = $_POST['columns'];
    
        $form = $this->GetForm($form_id);
        $old_db_name = $form['table_name'];
    
        // Update form details if necessary
        $this->updateForm($form_id, $formName, $tableName);
    
        // Rename the table if the new name is different from the old one
        if ($old_db_name !== $tableName) {
            $renameTableQuery = "ALTER TABLE `$old_db_name` RENAME TO `$tableName`";
            $this->db->insert($renameTableQuery);
        }
    
        // Fetch existing columns to compare
        $existingColumns = $this->getTableColumns($tableName);
        $columnTwo = $this->GetFields($form_id);
      
        foreach ($columns as $column) {
            $fieldName = $column['name'];
            $fieldDBName = $column['db_name'];
            $fieldType = $column['type'];
            $fieldLength = !empty($column['length']) ? $column['length'] : '';
            $fieldOptions = isset($column['options']) ? $column['options'] : [];
    
            $options = [];
           
            if (in_array('null', $fieldOptions)) {
                $options[] = 'NULL';
            } else {
                $options[] = 'NOT NULL';
            }
    
            $length = !empty($fieldLength) ? "($fieldLength)" : '';
            $optionString = implode(' ', $options);
    
            if (array_key_exists($fieldDBName, $existingColumns)) {
                // Modify the existing column
                $this->updateField($fieldName, $fieldDBName, $fieldType, $fieldLength, implode(",", $fieldOptions), $column['id']);
                $alterQuery = "MODIFY COLUMN `$fieldDBName` $fieldType $length $optionString";
            } else {

                if(isset($column['id'])) {
 
                    $colu = $this->GetField($column['id']);
                    $old_nm = $colu['db_name'];

                    $this->updateField($fieldName, $fieldDBName, $fieldType, $fieldLength, implode(",", $fieldOptions), $column['id']);
                    
                    $alterQuery = " CHANGE   `$old_nm`  `$fieldDBName` $fieldType $length $optionString";
                 
                  

                } else {
                    // Add a new column
                    $this->createField($fieldName, $fieldDBName, $fieldType, $fieldLength, implode(",", $fieldOptions), $form_id);
                    $alterQuery = "ADD COLUMN `$fieldDBName` $fieldType $length $optionString";
                }
                


            }
            
            $remainingColumns[] = $fieldDBName;
            // Execute the alter table query
            $sql = "ALTER TABLE `$tableName` $alterQuery";
            $this->db->insert($sql, []);
        }

        


    }
 
    public function getFormModules($meta_id, $meta_tag) {
        $query = "SELECT fm.*
                  FROM forms_relations fr
                  JOIN forms_meta fm ON fr.id = fm.relation_id
                  WHERE fr.meta_id = :meta_id 
                  AND fr.meta_tag = :meta_tag
                  GROUP BY fr.id, fm.meta_name";
        $params = array(':meta_id' => $meta_id, ':meta_tag' => $meta_tag);
        $forms = $this->db->fetchAll($query, $params);
    
        $result = [];
        foreach ($forms as $form) {
            $relation_id = $form['relation_id'];
            if (!isset($result[$relation_id])) {
                $result[$relation_id] = [];
            }
            $result[$relation_id][$form['meta_name']] = $form['meta_value'];
        }
    
     
    
        return $result;
    }

    


    private function getTableColumns($tableName) {

        $query = "SHOW COLUMNS FROM $tableName";
        $form = $this->db->fetchAll($query, []); 

        foreach($form as $column) {
            $columns[$column['Field']] = $column;
        }
       
        return $columns;
    }
    
    // Update form details function
    public function updateForm($form_id, $name, $table_name) {
        // Tworzymy zapytanie SQL do aktualizacji istniejącego formularza w tabeli forms
        $query = "UPDATE forms 
                  SET name = :name, table_name = :table_name 
                  WHERE id = :form_id";
        
        // Tworzymy tablicę parametrów do związania z zapytaniem SQL
        $params = array(
            ':name' => $name,
            ':table_name' => $table_name,
            ':form_id' => $form_id
        );
    
        // Wykonujemy zapytanie SQL za pomocą metody execute
        $this->db->update($query, $params);
    }
    
    // Update field details function
    private function updateField($name, $db_name, $type, $length, $options, $id) {
        // Tworzymy zapytanie SQL do aktualizacji istniejącego pola w tabeli forms_inputs
        $query = "UPDATE forms_inputs 
                  SET name = :name, db_name = :db_name, column_type = :column_type, column_length = :column_length, column_options = :column_options 
                  WHERE id = :id";
        
        // Tworzymy tablicę parametrów do związania z zapytaniem SQL
        $params = array(
            ':name' => $name,
            ':db_name' => $db_name,
            ':column_type' => $type,
            ':column_length' => $length,
            ':column_options' => $options,
            ':id' => $id
        );
    
        // Wykonujemy zapytanie SQL za pomocą metody execute
        $this->db->update($query, $params);
    }
    
    public function Remove($id) {
        
        $form = $this->GetForm($id);

        $table = $form['table_name'];

        $this->RemoveForm($id);
        $this->RemoveFields($id);
        $this->RemoveTable($table);

    }

    public function RemoveForm($id) {
        
        $query = "DELETE FROM forms WHERE id = :id";
        $params = array(':id' => $id);
        $form = $this->db->delete($query, $params); 

        return $form;

    }
    public function RemoveField($id) {
        
        $field = $this->GetField($id);
        $form = $this->GetForm($field['form_id']);
        $tableName = $form['table_name'];
        $existingColumn = $field['db_name'];

        $dropQuery = "DROP COLUMN `$existingColumn`";
        $sql = "ALTER TABLE `$tableName` $dropQuery";
        $this->db->delete($sql, []);

        $query = "DELETE FROM forms_inputs WHERE id = :id";
        $params = array(':id' => $id);
        $form = $this->db->delete($query, $params); 

        return $form; 

    }
    public function RemoveFields($id) {
        
        $query = "DELETE FROM forms_inputs WHERE form_id = :id";
        $params = array(':id' => $id);
        $form = $this->db->delete($query, $params); 

        return $form;

    }
    public function truncateTable($table) {
     
        $query = "TRUNCATE TABLE  :table";
        $params = array(':table' => $table);
        $form = $this->db->delete($query, $params); 

        return $form;

    }
    public function ManageForm() {

    }

    public function RemoveRecord($table, $id) {

        $query = "DELETE FROM $table WHERE id = :id";
        $params = array(':id' => $id);
        $form = $this->db->delete($query, $params); 

        return $form;

    }

    public function Truncate($id) {
     
        $form = $this->GetForm($id);

        $table = $form['table_name'];

        $this->truncateTable($table);

    }

    public function RemoveTable($table) {
        
        $query = "DROP TABLE :table";
        $params = array(':table' => $table);
        $form = $this->db->delete($query, $params); 

        return $form;

    }

    public function EditRecord($table, $id) {

        $columns = array_keys($_POST);
        $values = array_values($_POST);
        
        $params = implode(", ", array_map(function($col) { return "$col = :$col"; }, $columns));
        
        $query = "UPDATE $table SET $params WHERE id = :item_id"; // Zakładam, że pole klucza głównego w tabeli to 'id'
       
        $data = [];
        foreach ($columns as $index => $column) {
            $data[":$column"] = $values[$index];
        }
        $data[':item_id'] = $id; // Dodanie id do danych
        
        $form = $this->db->update($query, $data); // Przyjmuję, że masz funkcję update w obiekcie db do aktualizacji rekordu
          

    }
    public function CreateRecord($id, $table) {

        

        $columns = array_keys($_POST);
        $values = array_values($_POST);
 
        $columnsString = implode(", ", $columns);
 
        $params = implode(", ", array_map(function($col) { return ":$col"; }, $columns));
 
        $query = "INSERT INTO $table ($columnsString) VALUES ($params)";
       
        foreach ($columns as $index => $column) {
            $data[":$column"] = $values[$index];
        }

        $form = $this->db->insert($query, $data); 

    }
    public function GetForm($id) {
        
        $query = "SELECT * FROM forms WHERE id = :id";
        $params = array(':id' => $id);
        $form = $this->db->fetch($query, $params); 

        return $form;

    }

    public function GetField($id) {
        
        $query = "SELECT * FROM forms_inputs WHERE id = :id";
        $params = array(':id' => $id);
        $form = $this->db->fetch($query, $params); 

        return $form;

    }

    public function GetFields($id) {
        
        $query = "SELECT * FROM forms_inputs WHERE form_id = :id";
        $params = array(':id' => $id);
        $form = $this->db->fetchAll($query, $params); 

        return $form;

    }
    public function GetRecord($tableName, $id) {

        // Sprawdzenie czy użytkownik istnieje w bazie danych
        $query = "SELECT * FROM $tableName WHERE id = :id";
        $params = array(':id' => $id);
        $items = $this->db->fetch($query, $params); 
        
        return $items;

   }
   public function GetRecords($tableName, $param, $form_id) {

        $perPage = 10;
        
        $searchQuery = '';
        $page = isset($param[1]) ? $param[1] : 1;
        
        $offset = ($page - 1) * $perPage;

        // Sprawdzenie czy użytkownik istnieje w bazie danych 
        $sort = isset($_GET['sort']) ? $_GET['sort'] : ''; // Sprawdzamy, czy jest podana kolumna do sortowania
        $sort_type = isset($_GET['sort_type']) ? $_GET['sort_type'] : 'ASC'; // Domyślnie sortujemy rosnąco
    
        $sortQuery = '';
        if (!empty($sort)) {
            $sortQuery = "ORDER BY $sort $sort_type"; // Budujemy zapytanie sortowania
        }


        if (isset($_GET['search_term'])) {
            // Wybieramy operator LIKE tylko gdy został wybrany z formularza
            if ($_GET['search_operator'] == 'LIKE') {
                $searchQuery = "WHERE `{$_GET['search_field']}` LIKE '%{$_GET['search_term']}%'";
            } else {
                // Jeśli inny operator został wybrany, użyj go
                $searchQuery = "WHERE `{$_GET['search_field']}` {$_GET['search_operator']} '{$_GET['search_term']}'";
            }
        }  

        $joinClauses = '';
        $fields = $this->GetFields($form_id);
        foreach ($fields as $field) {
            $relation = $this->FindRelation($field['id'], 'forms_relation_input');
            if ($relation) {
                $relatedTable = $this->GetField($relation['meta_value']);
                $relatedForm = $this->GetForm($relatedTable['form_id']);
                $joinClauses .= " LEFT JOIN {$relatedForm['table_name']} AS t_{$field['db_name']} ON {$tableName}.{$field['db_name']} = t_{$field['db_name']}.id";
            }

            $relationMeta = $this->FindRelation($field['id'], 'forms_relations');
            if ($relationMeta) {
                
                //$relatedTable = $this->GetField($relationMeta['meta_value']);
                //$relatedForm = $this->GetForm($relatedTable['form_id']);
                $joinClauses .= " LEFT JOIN forms_meta AS t_{$field['db_name']}_value ON {$tableName}.{$field['db_name']} = t_{$field['db_name']}_value.meta_value AND t_{$field['db_name']}_value.meta_name = 'relation_value'";
                $joinClauses .= " LEFT JOIN forms_meta AS t_{$field['db_name']}_title ON t_{$field['db_name']}_value.relation_id = t_{$field['db_name']}_title.relation_id AND t_{$field['db_name']}_title.meta_name = 'relation_title'";
              
            } 

        }
 

        $totalRecords = $this->CountRecords($tableName, $searchQuery);
        $totalPages = ceil($totalRecords / $perPage);


        $query = "SELECT $tableName.*";
        foreach ($fields as $field) {
            $relation = $this->FindRelation($field['id'], 'forms_relation_input');
            if ($relation) {
                $relatedTable = $this->GetField($relation['meta_value']);
                $relatedForm = $this->GetForm($relatedTable['form_id']); 
                $query .= ", t_{$field['db_name']}.{$relatedTable['db_name']} AS {$field['db_name']}";
            }
             // Dodanie pól z 'forms_relations'
            $relationMeta = $this->FindRelation($field['id'], 'forms_relations');
            if ($relationMeta) {
                $relatedTable = $this->GetField($relationMeta['meta_value']);
                $query .= ", t_{$field['db_name']}_title.meta_value AS {$field['db_name']}";
              
            }
        }
        $query .= " FROM $tableName $joinClauses $searchQuery $sortQuery LIMIT $offset, $perPage";
        
      
        $items = $this->db->fetchAll($query, []); 
       // $items = $this->ReformatItems($form_id,$items);


        $startPage = max(1, $page - 3);
        $endPage = min($totalPages, $page + 3);
    
        return [
            'items' => $items,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'startPage' => $startPage,
            'endPage' => $endPage
        ];

   }
  
   public function Relations($form_id) {

    $relations = [];
    $fields = $this->GetFields($form_id);
    foreach ($fields as $field) {

        $relation = $this->FindRelation($field['id'], 'forms_relation_input');
       
        if ($relation) {

            $relatedTable = $this->GetField($relation['meta_value']);
            $relatedForm = $this->GetForm($relatedTable['form_id']);
            
            $list = $this->GetRowsToRelation($relatedForm['table_name'], $relatedTable['db_name']);
          
            $relations[$field['db_name']] = $list;

            
        } else {

            $relation_meta = $this->FindRelation($field['id'], 'forms_relations');
            if($relation_meta) {

                $relations[$field['db_name']] = $this->getFormModules($field['id'], 'forms_relations');

            }
            
            

        }
        
    }


  
    return $relations;

  
   }

   public function GetRowsToRelation($table, $field) {
        
    // Sprawdzenie czy użytkownik istnieje w bazie danych
    $query = "SELECT id as relation_value, $field as relation_title FROM $table ";
    $items = $this->db->fetchAll($query, []); 
    
    return $items;

    

}

   public function FindRelation($input_id, $meta_tag) { 
        
        // Sprawdzenie czy użytkownik istnieje w bazie danych
        $query = "
        SELECT fm.*
            FROM forms_meta fm
                JOIN forms_relations fr ON fm.relation_id = fr.id
                WHERE fr.meta_id = :meta_id
                AND fr.meta_tag = :meta_tag ;
        
        ";
        $items = $this->db->fetch($query, [':meta_id' => $input_id, ':meta_tag' => $meta_tag]); 
        
        return $items;

  }

   public function CheckChecked() {
    
   }


   public function CountRecords($tableName, $searchQuery) {

        // Sprawdzenie czy użytkownik istnieje w bazie danych
        $query = "SELECT * FROM $tableName $searchQuery ";
        $items = $this->db->count($query, []); 
        
        return $items;

    }


    public function list() {

         // Sprawdzenie czy użytkownik istnieje w bazie danych
         $query = "SELECT * FROM forms ";
         $items = $this->db->fetchAll($query, []); 
         
         return $items;

    }

    public function createForm($name, $table_name) {

        $query = "INSERT INTO forms SET name = :name, table_name = :table_name";
        $params = array(':name' => $name, ':table_name' => $table_name);
        $form_id = $this->db->insert($query, $params); 

        return $form_id;
 
    }
    public function createField($name, $db_name, $column_type, $column_length, $options, $form_id) {
 
        $query = "INSERT INTO forms_inputs SET name = :name, db_name = :db_name, column_type = :column_type, column_length = :column_length, column_options = :column_options, form_id = :form_id";
        $params = array(':name' => $name, ':db_name' => $db_name, ':column_type' => $column_type, ':column_length' => $column_length, ':form_id' => $form_id, ':column_options' => $options );
        $form_id = $this->db->insert($query, $params); 

        return $form_id;

    }
  
}

?>