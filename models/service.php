<?php
/**
 * 
 * Clase para obtner informacion de servicios y agregar un nuevo servicio
 *  
 * @author Manuel Gabriel <ingmanuelgabriel@gmail.com|ingmanuelgabriel@hotmail.com>
 * @copyright Copyright (c) 2020, Manuel Gabriel | WELMASTER
 *
 *
**/

include_once dirname(__file__,2)."/config/conexion.php";
class Service
{
	private $db;
	private $data;//array of form post
	public $message;//message of result
	public $type;//type of menssage: red|green|orange|blue|purple|dark
	public $time;//set stopwatch

	function __construct()
	{
		$this->db   = new Conexion();
		$this->message = '';
		$this->type = 'green';
		$this->time = true;
		$this->data = array();
	}

	public function getServicesAct():?array
	{
		$rows = $this->db->get_results("SELECT * FROM servicios WHERE Estado = 1");
		if ( !$rows ) return NULL;
		return $rows;
	}

	public function getServices():?array
	{
		$rows = $this->db->get_results("SELECT * FROM servicios ");
		if ( !$rows ) return NULL;
		return $rows;
	}

	public function getServiceByDesc($value):?array
	{
		$serch = $this->db->escape($value);
		if( !empty($serch) ){
			$rows = $this->db->get_results("SELECT T0.Id_Servicio, T0.Descripcion, T0.Costo, T0.Precio
				FROM servicios T0
				WHERE T0.Descripcion LIKE '%" .$serch. "%'
				AND T0.Estado = 1  LIMIT 6");
			if ( !$rows ) return NULL;
			return $rows;
		}else{
			return NULL;
		}
	}

	public function getServiceById($id):?OBJECT
	{
		if( isIntN($id) ){
			$row = $this->db->get_row("SELECT * FROM servicios
				WHERE Id_Servicio = '$id' ");
			if (!$row) return NULL;
			return $row;
		}else{
			return NULL;
		}
	}

	//valida que ningun campo este vacio, aplica formato correspondiente a cada campo
	public function validData($post,$option=null):bool 
	{
		if( !empty($option) ){
			if ( empty($post['id']) ) {//valida que el campo Id tenga un valor
				$this->message = '<strong>Error [1006]</strong>: Los datos no fueron procesados correctamente. Notifique el error.';
				$this->type = 'red';
				$this->time = false;
				return false;
			}
			$this->data['Id'] = (isIntN( $post['id']) )? $this->db->escape($post['id']) :'';
		}
		
		$this->data['descripction'] = ( !empty($post['descripction']) )? camelCase( $this->db->escape($post['descripction']) ) : '' ;
		$this->data['cost'] = ( !empty($post['cost']) )? $this->db->escape($post['cost']) : '';
		$this->data['price'] = ( !empty($post['price']) )? $this->db->escape($post['price']) : '';
		$this->data['duration'] = ( !empty($post['duration']) )? $this->db->escape($post['duration']) : '';
		
		foreach ($this->data as $key => $value) {
			if (empty($value)) {
				break;
				$this->message = '<strong>Error [1001]</strong>: Un campo del formulario esta vació o no cumple con el formato correcto. Revise los valores ingresados';
				$this->type = 'red';
				return false;
			}
		}
		if (isset($post['state'])) {
			$this->data['state'] = ( $post['state'] )? 1 : 0;
		}

		if ( !empty($option) ) {
			return $this->editData($this->data); //Update User
		}else{
			return $this->newData($this->data) ;//New User
		}
	}

	private function newData($insert):bool
	{
		$query1 = "INSERT INTO servicios
			(Id_Servicio, Descripcion, Precio, Costo, Duracion, Estado) 
			VALUES
			(NULL,'".$insert['descripction']."','".$insert['price']."','".$insert['cost']."','".$insert['duration']."', '1' )";	
		if ( $this->db->query($query1) ) {//guarda informacion de empleado
			$result = true;
		}else{
			$this->message = '<strong>Error [1003]</strong>: Los datos no fueron guardados debido a un erro interno. Intentelo de nuevo.';
			$this->type = 'red';
			$result = false;
		}
		return $result;
	}

	private function setUpdate($array):bool
	{
		$stored = $this->getServiceById($array['Id']);
		$set =  '';

		if ($stored->Descripcion != $array['descripction']) {
			$set .= "Descripcion = '".$array['descripction']."',";
		}
		if ($stored->Precio != $array['price']) {
			$set .= "Precio = '".$array['price']."',";
		}
		if ($stored->Costo != $array['cost']) {
			$set .= "Costo = '".$array['cost']."',";
		}
		if (formatHM($stored->Duracion) != $array['duration']) {
			$set .= "Duracion = '".$array['duration']."',";
		}
		if ($stored->Estado != $array['state']) {
			$set .= "Estado = '".$array['state']."',";
		}
		$set = trim($set, ',');//Elimina el ultimo caracter definido ','
		$this->data = array();//reset array
		$this->data['set'] = $set;

		if ( empty($set) ) {
			return false;
		}else{
			return true;
		}
	}

	private function editData($array):bool
	{
		if( $this->setUpdate($array) ){//verifica si hay cambios para aplicar
				$query0 = "UPDATE servicios  
					SET ".$this->data['set']."
					WHERE Id_Servicio = '".$array['Id']."' ";
				if($this->db->query($query0)){
					$result = true;
				}else{
					$this->message = '<strong>Error [1003]</strong>: Los datos no fueron guardados debido a un erro interno. Intentelo de nuevo.';
					$this->type = 'red';
					$result = false;
				}
				//$this->db->debug();
		}else{
			
			$this->message = 'No se ha detectado ningun cambio';
			$this->type = 'orange';
			$result = false;
		}
		return $result;
	}

	private function deleteUser( $id ):bool
	{
		if ( isIntN($id) ) {
			$query0 = "DELETE FROM servicios WHERE Id_Servicio = '$id' ";
			if( $this->db->query($query0) ){
				$result = true;
			}else{
				$this->message = '<strong>Error [1003]</strong>: Los datos no fueron guardados debido a un erro interno. Intentelo de nuevo.';
				$this->type = 'red';
				$result = false;
			}
		}else{
			$this->message = '<strong>Error [1006]</strong>: Los datos no fueron procesados correctamente. Notifique el error.';
			$this->type = 'red';
			$this->time = false;
			$result = false;
		}
		return $result;
	}

	public function disableEnable($id,$option=null):bool
	{
		if ( isIntN($id) ) {
			$state = ( !empty($option) )? 1 : 0 ;
			$query0 = "UPDATE servicios 
				SET Estado = $state
				WHERE Id_Servicio = '$id' ";
			if( $this->db->query($query0) ){
				$result = true;
			}else{
				$this->message = '<strong>Error [1003]</strong>: Los datos no fueron guardados debido a un erro interno. Intentelo de nuevo.';
				$this->type = 'red';
				$result = false;
			}
		}else{
			$this->message = '<strong>Error [1006]</strong>: Los datos no fueron procesados correctamente. Notifique el error.';
			$this->type = 'red';
			$this->time = false;
			$result = false;
		}
		return $result;
	}
}
?>