<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;

class AbrSearchController extends Controller
{
    protected $url;
	
    public function get(Request $request)
    {
		if(empty($request->name) && empty($request->abn)) {
            return response([], 200);
        }
        $this->init($request);
        $response = Curl::to($this->url)->get();
        $resp = $this->addFields($this->extract($request, $response)); 
        return response($resp, 200);
    }

    protected function init(Request $request) 
    {
        $q = "";
        if ($request->has('name') && !empty($request->name)) {
            $this->url = env('ABR_SEARCH_COMPANY_URL');
            $q = rawurlencode($request->name);
        } 
        if ($request->has('abn') && !empty($request->abn)) {
            $this->url = env('ABR_SEARCH_ABN_URL');
            $q = $request->abn;
        }
        $this->url = str_replace('{q}', $q, $this->url);
    }

    protected function extract(Request $request, $result) 
    {
        list($callBack) = explode('(', $result);
        $res = json_decode(substr(str_replace($callBack.'(', '', $result), 0, strlen(str_replace($callBack.'(', '', $result))-1), true);
        if ($callBack === 'nameCallback') {
            return empty($res['Message']) ? $res['Names'] : []; 
        } 
        return empty($res['Message']) ? [$res] : [];
    }

    protected function addFields($results)
    {
        foreach($results as $k => $res) {
            $results[$k]['name'] = $res['EntityName'] ?? $res['Name'];
            $results[$k]['abn_acn'] = $res['Abn'];
            $results[$k]['info'] = ($res['EntityName'] ?? $res['Name']) . ' ' . $res['Abn'];
        }
        return $results;
    }
}
