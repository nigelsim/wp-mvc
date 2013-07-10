<?php

class MvcAdminController extends MvcController {

	public $is_admin = true;

	public function index() {
		$this->set_objects();
	}

	public function add() {
		$this->create_or_save();
	}

	public function edit() {
		$this->verify_id_param();
		$this->create_or_save();
		$this->set_object();
	}

	public function delete() {
		$this->verify_id_param();
		$this->set_object();
		if (!empty($this->object)) {
			$this->model->delete($this->params['id']);
			$this->flash('notice', 'Successfully deleted!');
		} else {
			$this->flash('warning', 'A '.MvcInflector::humanize($this->model->name).' with ID "'.$this->params['id'].'" couldn\'t be found.');
		}
		if (!empty($this->params['redirect'])) {
			$url = $this->params['redirect'];
		} else {
			$url = MvcRouter::admin_url(array('controller' => $this->name, 'action' => 'index'));
		}
		$this->redirect($url);
	}

	public function verify_id_param() {
		if (empty($this->params['id'])) {
			die('No ID specified');
		}
	}

	public function create_or_save($redirect=true) {
		if (!empty($this->params['data'][$this->model->name])) {
			$object = $this->params['data'][$this->model->name];
			if (empty($object['id'])) {
				$this->model->create($this->params['data']);
                                
                                $uploads = array();
                                $uploads[$this->model->name][$this->model->primary_key] = $this->model->insert_id;
                                $this->setup_uploads($uploads);
                                if (count($uploads[$this->model->name]) > 1) {
                                    $this->model->save($uploads);
                                }
                                
				$this->flash('notice', 'Successfully created!');
                                if (!$redirect) {
                                    // return the ID if we aren't redirecting
                                    return $this->model->insert_id;
                                }
				if (!empty($this->params['redirect'])) {
					$url = $this->params['redirect'];
				} else {
					$id = $this->model->insert_id;
					$url = MvcRouter::admin_url(array('controller' => $this->name, 'action' => 'edit', 'id' => $id));
				}
				$this->redirect($url);
			} else {
                                $this->setup_uploads($this->params['data']);
				if ($this->model->save($this->params['data'])) {
                                        if (!$redirect) {
                                            return $object['id'];
                                        }
					$this->flash('notice', 'Successfully saved!');
					if (!empty($this->params['redirect'])) {
						$url = $this->params['redirect'];
						$this->redirect($url);
					} else {
						$this->refresh();
					}
				} else {
                                    if (!$redirect) {
                                        return false;
                                    }
					$this->flash('error', $this->model->validation_error_html);
				}
			}
		}
	}
        
        
        /**
         * Setup the upload file/fields based on the field name and the ID.
         * 
         * @param type $params
         * @return type
         */
        public function setup_uploads(&$params = NULL) {
            if ($params === NULL) {
                $params = array();
            }
            $upload_dir = wp_upload_dir();
            
            foreach ($this->model->files as $field_name) {
                if (strlen($_FILES['data']["tmp_name"][$this->model->name][$field_name]) == 0) continue;
                $dir_name = "/cc_beach_analytics/reports/".$this->model->name."/".$params[$this->model->name][$this->model->primary_key];
                mkdir($upload_dir['basedir'].$dir_name, 0777, true);
                $file_name = $dir_name."/".$field_name;
                copy($_FILES['data']["tmp_name"][$this->model->name][$field_name], $upload_dir['basedir'].$file_name);
                $params[$this->model->name][$field_name] = $file_name;
            }
            return $params;
        }

	public function create() {
		if (!empty($this->params['data'][$this->model->name])) {
			$id = $this->model->create($this->params['data']);
			$url = MvcRouter::admin_url(array('controller' => $this->name, 'action' => 'edit', 'id' => $id));
			$this->flash('notice', 'Successfully created!');
			$this->redirect($url);
		}
	}

	public function save() {
		if (!empty($this->params['data'][$this->model->name])) {
			if ($this->model->save($this->params['data'])) {
				$this->flash('notice', 'Successfully saved!');
				$this->refresh();
			} else {
				$this->flash('error', $this->model->validation_error_html);
			}
		}
	}

	public function set_objects() {
		$this->init_default_columns();
		$this->process_params_for_search();
		$collection = $this->model->paginate($this->params);
		$this->set('objects', $collection['objects']);
		$this->set_pagination($collection);

	}

	public function set_pagination($collection) {
		$url_params = MvcRouter::admin_url_params(array('controller' => $this->name));
		$params = $this->params;
		unset($params['page_num']);
		$params['page'] = $url_params['page'];
		$this->set('pagination', array(
			'base' => get_admin_url().'admin.php%_%',
			'format' => '?page_num=%#%',
			'total' => $collection['total_pages'],
			'current' => $collection['page'],
			'add_args' => $params
		));
	}

	public function after_action($action) {
	}

	protected function process_params_for_search() {
		$this->params['page'] = empty($this->params['page_num']) ? 1 : $this->params['page_num'];
		if (!empty($this->params['q']) && !empty($this->default_searchable_fields)) {
			$this->params['conditions'] = $this->model->get_keyword_conditions($this->default_searchable_fields, $this->params['q']);
			if (!empty($this->default_search_joins)) {
				$this->params['joins'] = $this->default_search_joins;
				$this->params['group'] = $this->model->name.'.'.$this->model->primary_key;
			}
		}
	}

	protected function init_default_columns() {
		if (empty($this->default_columns)) {
			MvcError::fatal('No columns defined for this view.  Please define them in the controller, like this:
				<pre>
					class '.MvcInflector::camelize($this->name).'Controller extends MvcAdminController {
						var $default_columns = array(\'id\', \'name\');
					}
				</pre>');
		}
		$admin_columns = array();
		foreach ($this->default_columns as $key => $value) {
			if (is_array($value)) {
				if (!isset($value['label'])) {
					$value['label'] = MvcInflector::titleize($key);
				}
			} else if (is_integer($key)) {
				$key = $value;
				if ($value == 'id') {
					$value = array('label' => 'ID');
				} else {
					$value = array('label' => MvcInflector::titleize($value));
				}
			} else {
				$value = array('label' => $value);
			}
			$value['key'] = $key;
			$admin_columns[$key] = $value;
		}
		$this->default_columns = $admin_columns;
	}

}

?>