<?php

class MvcHtmlHelper extends MvcHelper {

	public function link($text, $url, $options=array()) {
		if (is_array($url)) {
			if (!empty($options['extraparams'])) {
				$url['extraparams'] = $options['extraparams'];
			}
			$url = MvcRouter::public_url($url);
		} else {
			if (!empty($options['extraparams']) && is_array($options['extraparams'])) {
				foreach ($options['extraparams'] as $key => $value){
					$url .= '&'.$key.'='.urlencode($value);
				}
			}
		}
		$defaults = array(
			'href' => $url,
			'title' => $text,
		);
		$options = array_merge($defaults, $options);
		$attributes_html = self::attributes_html($options, 'a');
		$html = '<a'.$attributes_html.'>'.$text.'</a>';
		return $html;
	}

	public function object_url($object, $options=array()) {
		$defaults = array(
			'id' => $object->__id,
			'action' => 'show',
			'object' => $object
		);
		$options = array_merge($defaults, $options);
		$url = MvcRouter::public_url($options);
		return $url;
	}

	public function object_link($object, $options=array()) {
		$url = self::object_url($object, $options);
		$text = empty($options['text']) ? $object->__name : $options['text'];
		return self::link($text, $url, $options);
	}

	public function admin_object_url($object, $options=array()) {
		$defaults = array(
			'id' => (is_object($object)) ? $object->__id : null,
			'object' => $object
		);
		$options = array_merge($defaults, $options);
		$url = MvcRouter::admin_url($options);
		return $url;
	}

	public function admin_object_link($object, $options=array()) {
		$url = self::admin_object_url($object, $options);
		$text = empty($options['text']) ? $object->__name : $options['text'];

		$innerOptions = empty($options['linkoptions']) ? array() : $options['linkoptions'];
		$extraParams = empty($options['extraparams']) ? array() : array('extraparams' => $options['extraparams']);
		$innerOptions = array_merge($innerOptions, $extraParams);
		return self::link($text, $url, $innerOptions);
	}

	public function __call($method, $args) {
		if (property_exists($this, $method)) {
			if (is_callable($this->$method)) {
				return call_user_func_array($this->$method, $args);
			}
		}
	}

}

?>