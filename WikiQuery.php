<?php

class WikiQueryException extends Exception{}
class WikiQueryPageSaveException extends Exception{}

class WikiQuery {
	
	private $curl;
	private $auth;
	
	function __construct( WikiAuth $auth, Curl $curl ) {
		
		$this->auth = $auth;
		$this->curl = $curl;
	}
	
	function query( $data ) {
		
		$data['action'] = 'query';
		$data['format'] = 'php';

		$response = $this->c->get( $this->api, $data );

		return unserialize($response);
	}
	
	function edit( $data ) {
		
		$c = $this->curl;
		$c->headers['Content-Type'] = "application/x-www-form-urlencoded"
		
		$data['action'] = 'edit';
		$data['format'] = 'php';

		$response = $c->post( $this->api, $data );

		return unserialize($response);
	}
	
	function get_page_data( $title ) {
		
		$data = array(
			'prop' => 'info|revisions',
			'intoken' => 'edit',
			'titles' => $title,
			'rvprop' => 'content' // need to get page text
		);
		
		$response = $this->query( $data );
		
		// Check for errors
		if ( isset( $response['error'] ) )
			throw new WikiQueryException();

		return array_pop( $response['query']['pages'] ); // get the page (there should only be one)
	}
	
	function save_page_data( $data ) {
		
		// TODO: precons to ensure proper data given
		
		$data['md5'] => md5( $data['text'] );
		$data['bot'] => "true";
		
		$response = $this->edit( $data );
		
		if ( $response['edit']['result'] != "Success" )
			throw new WikiQueryPageSaveException();
	}
}
