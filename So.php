<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/10/1
 * Baidu searcher
 */

namespace QL\Ext;

use QL\Contracts\PluginContract;
use QL\QueryList;

class So implements PluginContract
{
    protected $ql;
    protected $keyword;
    protected $pageNumber = 10;
    protected $httpOpt = [];
    const API = 'https://www.so.com/s';
    const RULES = [
      'title' => ['.res-list>h3','text'],
      'link' => ['.res-list>h3>a','href']
    ];
    const RANGE = '.result';

    public function __construct(QueryList $ql, $pageNumber)
    {
        $this->ql = $ql->rules(self::RULES)->range(self::RANGE);
        $this->pageNumber = $pageNumber;
    }

    public static function install(QueryList $queryList, ...$opt)
    {
        $name = $opt[0] ?? 'so';
        $queryList->bind($name,function ($pageNumber = 10){
            return new So($this,$pageNumber);
        });
    }

    public function setHttpOpt(array $httpOpt = [])
    {
        $this->httpOpt = $httpOpt;
        return $this;
    }

    public function search($keyword)
    {
        $this->keyword = $keyword;
        return $this;
    }

    public function page($page = 1,$realURL = false)
    {
        return $this->query($page)->query()->getData(function ($item) use($realURL){
            $realURL && $item['link'] = $this->getRealURL($item['link']);
            return $item;
        });
    }

    public function getCount()
    {
        $count = 0;
        $text =  $this->query(1)->find('.nums')->text();
        if(preg_match('/[\d,]+/',$text,$arr))
        {
            $count = str_replace(',','',$arr[0]);
        }
        return (int)$count;
    }

    public function getCountPage()
    {
        $count = $this->getCount();
        $countPage = ceil($count / $this->pageNumber);
        return $countPage;
    }

    protected function query($page = 1)
    {
        $this->ql->get(self::API,[
            'wd' => $this->keyword,
            'rn' => $this->pageNumber,
            'pn' => $this->pageNumber * ($page-1)
        ],$this->httpOpt);
        return $this->ql;
    }

    protected  function getRealURL($url)
    {
        //得到百度跳转的真正地址
        $header = $this->get_url_headers($url);
        if (strpos($header[0],'301') || strpos($header[0],'302'))
        {
            if(is_array($header['Location']))
            {
                //return $header['Location'][count($header['Location'])-1];
                return $header['Location'][0];
            }
            else
            {
                return $header['Location'];
            }
        }
        else
        {
            return $url;
        }
    }
/*自建项目*/
public function get_url_headers($url,$timeout = 10)
{
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,true);
        curl_setopt($ch,CURLOPT_NOBODY,true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);

        $data = curl_exec($ch);
        $data = preg_split('/\n/',$data);

        $data = array_filter(array_map(function($data) {
                $data = trim($data);
                if($data) {
                        $data = preg_split('/:\s/',trim($data),2);
                        $length = count($data);
                        switch($length) {
                                case 2:
                                        return array($data[0] => $data[1]);
                                        break;
                                case 1:
                                        return $data;
                                        break;
                                default:
                                        break;
                        }
                }
        },$data));

        sort($data);
/*
        foreach($data as $key => $value) {
                $itemKey = array_keys($value)[0];
                if(is_int($itemKey)){
                        $data[$key] = $value[$itemKey];
                }elseif(is_string($itemKey)){
                        $data[$itemKey] = $value[$itemKey];
                unset($data[$key]);
                }
        }

        return$data;*/
        foreach($data as $value) {
                foreach($value as $k => $v){
                        $aa[$k]=$v;
                }
        }


        return $aa;
}
/*自建完成*/

}
