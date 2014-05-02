<?php

Class MediaApiClient {
	private $url;
	private $headers;
        private $access_token =null;
        private $item_count=0;
        private $page_count=0;
        private $item_count_perpage = 0;

	public function __construct($url) {
            $this->url = $url;



	}

        /**
         *
         * @return type
         */
        public function getItemCount() {
            return $this->item_count;
        }
        /**
         *
         * @return type
         */
        public function getPageCount() {
            return $this->page_count;
        }
        /**
         *
         */
        public function getItemCountPerPage() {
            return $this->item_count_perpage;

        }


        public function getHeaders() {
            return $this->headers;
        }

        /**
         *
         * @param type $page
         * @return type
         */
	public function getMedia($params) {
            if(!$this->access_token) {
                $access_token =  mediaapi_get_accesstoken();
                $this->access_token = $access_token;
            }

            if(isset($params['page'])) {
            $url = "$this->url" . "/page/" . $params['page'];
            } elseif(isset($params['uuid'])) {
                $url = $this->url . "/" .$params['uuid'];
            } else {
                $url = $this->url;
            }
            $header = array('Content-Type: application/json', "Authorization: Bearer " .$access_token);
            $process = curl_init();
            curl_setopt($process, CURLOPT_HEADER, true);
            curl_setopt($process, CURLOPT_HTTPHEADER, $header);
            curl_setopt($process, CURLOPT_URL, $url);
            curl_setopt($process, CURLOPT_RETURNTRANSFER,TRUE);


            $result = curl_exec($process);

            $header_size = curl_getinfo($process, CURLINFO_HEADER_SIZE);

            $this->headers = $this->get_headers_from_curl_response($result);
            $body = substr($result, $header_size);


            $this->item_count = !empty($headers["item-count"]) ? $headers["item-count"] : 1;
            $this->page_count = !empty($headers["page-count"]) ? $headers["page-count"] : 1;
            $this->item_count_perpage = !empty($headers['item-count-per-page']) ? $headers['item-count-per-page'] : 1;
            curl_close($process);
            return $body;


        }



function get_headers_from_curl_response($response)
{
    $headers = array();
    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

    foreach (explode("\r\n", $header_text) as $i => $line)
        if ($i === 0)
            $headers['http_code'] = $line;
        else
        {
            list ($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }

    return $headers;
}

}


