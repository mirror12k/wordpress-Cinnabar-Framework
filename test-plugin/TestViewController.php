<?php

class TestViewController extends ViewController
{
	public function template_args()
	{
		return array('name' => $this->page['path']);
	}
}

