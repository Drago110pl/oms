<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use App\Models\Form;
use Core\Error;
use Core\View;
class Forms extends Controller
{ 
    public function index() {
        // Przykładowe zapytanie 
        $Form = new Form();

        $data['menu'] = $Form->list();
     
        View::render('Admin/Form/Home.html', $data, "admin");
        
    }
    public function Add() {
        // Przykładowe zapytanie 

        $Form = new Form();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $Form->createTable();
        }


        View::render('Admin/Form/Add.html', [], "admin");
        
    }
    public function Record($param) {
        
        $Form = new Form();

        $form = $Form->GetForm($param[0]);
        $fields = $Form->GetFields($param[0]);

        $data['menu'] = $Form->list();
        $data['form'] =  $form;
        $data['fields'] = $fields;
        $data['relations'] = $Form->Relations($param[0]);


       
         
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $Form->CreateRecord($param[0], $form['table_name']);
        }

        View::render('Admin/Form/Record.html',  $data, "admin");

    }

    public function Relation() {

        View::render('Admin/Form/Relation.html', [], "admin");

    }

    public function Edit($param) {
        // Przykładowe zapytanie 

        $Form = new Form();
      
        $form = $Form->GetForm($param[0]);
        $fields = $Form->GetFields($param[0]);

        $data['menu'] = $Form->list();
        $data['form'] =  $form;
        $data['fields'] = $fields;
        $data['row'] = $Form->GetRecord($form['table_name'], $param[1]);
        $data['relations'] = $Form->Relations($param[0]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $Form->EditRecord($form['table_name'], $param[1]);
        }
 
        View::render('Admin/Form/Edit.html', $data, "admin");


    }
    public function Manage($param) {

        $Form = new Form();
      
        $form = $Form->GetForm($param[0]);
        $fields = $Form->GetFields($param[0]);

        $data['menu'] = $Form->list();
        $data['form'] =  $form;
        $data['fields'] = $fields;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          
            $Form->editTable($param[0]);
        }
 
        View::render('Admin/Form/Manage.html', $data, "admin");

    }

    public function Delete($param) {

        $Form = new Form();
        $form = $Form->GetForm($param[0]);

        $Form->RemoveRecord($form['table_name'], $param[1]);

        $this->redirect("../");

    }
    public function Remove($param) {
        // Przykładowe zapytanie 

        $Form = new Form();

        $Form->Remove($param[0]);

        $this->redirect("../");


    }
    public function Truncate($param) {
        
        $Form = new Form();

        $Form->Truncate($param[0]);

        $this->redirect("../");

    }
    public function View($param) {
 
        $Form = new Form(); 
       
        $form = $Form->GetForm($param[0]);
        $fields = $Form->GetFields($param[0]);

        $rows = $Form->GetRecords($form['table_name'], $param, $form['id']);
        
        $data['menu'] = $Form->list();
        $data['form'] =  $form;
        $data['fields'] = $fields;
        $data['rows']= $rows;

        $data['forms_view_menu'] = $Form->getFormModules($param[0], 'forms_view_menu');
        

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!empty($_POST['checked'])) {
                $Form->CheckChecked();
            }
           
            if(isset($_POST['search_field'])) {  
                $this->redirect($this->app['url']."/admin/form/view/".$param[0]."/1/".$_POST['search_field']."/".$_POST['search_operator']."/".$_POST['search_term']);
            }
        }
        

     
        View::render('Admin/Form/View.html',  $data, "admin");

    }

} 

?>