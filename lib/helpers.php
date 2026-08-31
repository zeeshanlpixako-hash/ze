<?php
require_once(__DIR__."/new/simple_html_dom.php");
function isValidMinWordsLimit($string, $limit = 50){
    $temp = trim(strip_tags(unescape(html_entity_decode($string))));
    $count = str_word_count($temp);
    
    return $count > $limit ? true : false;
}

function unescape($string) {
    return str_replace(
        array( '\0'  , '\n', '\r', '\Z'  , '\"', '\\\'', '\\\\' ),
        array( "\x00", "\n", "\r", "\x1a", '"' , '\''  , '\\'   ),
        $string
    );
}

function cleanString($string){
    $db = new db();
    $string = html_entity_decode($string);
    $string = strip_tags($string, '<p><strong><br><img><ul><ol><li><h1><h2><h3><h4><h5><h6><table><tbody><tr><th><td>');
    $string =trim(preg_replace('~[\r\n\t]~', '',$string));
    if(empty($string)){
        $string = NULL;
    }else{
        $string = $db->escape(htmlentities($string, ENT_COMPAT, 'UTF-8', false));
    }
    return $string;
}

function cleanStringV2($string){
    $db = new db();
    $string = html_entity_decode($string);
    $string = strip_tags($string, '<p><strong><br><img><ul><ol><li><h1><h2><h3><h4><h5><h6><table><tbody><tr><th><td>');
    $string =trim(preg_replace('~[\r\n\t]~', ' ',$string));
    if(empty($string)){
        $string = NULL;
    }else{
        $string = $db->escape(htmlentities($string, ENT_COMPAT, 'UTF-8', false));
    }
    return $string;
}

// function cleanString($string){
//     $db = new db();
//     $current_encoding = mb_detect_encoding($string, 'UTF-8', true);
//     $string = ($current_encoding == "UTF-8") ? $string : utf8_encode($current_encoding);
//     $string = html_entity_decode($string);
//     $string = strip_tags($string, '<p><strong><br><img><ul><ol><li><h1><h2><h3><h4><h5><h6><table><tbody><tr><th><td>');
//     $string =trim(preg_replace('~[\r\n\t]~', '',$string));
//     if(empty($string)){
//     $string = NULL;
//     }else{
//     $string = $db->escape(htmlentities($string, ENT_COMPAT, 'UTF-8', false));
//     }
//     return $string;
// }


    function isCategoryValid_helper($categoriesStr, $baseCategories = ['Videos','Video','Audio','Radio','TV','Televisión','Broadcast','podcast','Multimedia'])
    {
        foreach ($baseCategories as $bc)
        {
            if (stripos($categoriesStr, $bc) !== false) {
                return false;
            }
        }

        return true;
    }

    function removeJs($string)
    {
        $html = str_get_html($string);
        if ($scripts = $html->find('script')) {
            foreach ($scripts as $script) {
                $script->outertext = "";
            }
        }

        return $html->innertext;
    }

    function removeCss($string)
    {
        $html = str_get_html($string);
        if ($styles = $html->find('style')) {
            foreach ($styles as $style) {
                $style->outertext = "";
            }
        }

        return $html->innertext;
    }

    /**
     * returns data as string
     */
    function getDataByCurl_helper($link)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    function isInvalidAuthor($author){
        $find = array("reuters","afp","notimex","ap","cnn","xinhua");
        $author = strtolower($author);
        $author = html_entity_decode($author);
        $newString = str_replace(array(" ",'|','-','_','/'),",",$author);
        $chunks = explode(",",$newString);
        foreach ($chunks as $key => $chunk) {
        if(in_array($chunk, $find))
            return true;
        }
        if(strpos($author,"agencia europa press") !== false || strpos($author,"financial times") !== false)
            return true;
        return false;
    }

    /**
     * adds missing base url of the source site in 
     * src attribute of any img tags inside the html string
     */
    function addBaseURLtoImages($htmlString, $baseUrl)
    {
        $html = str_get_html($htmlString);
        if ($imgs = $html->find('img')) {
            foreach ($imgs as $k=>$img)
            {
                if (stripos($img->src,$baseUrl) === FALSE) {
                    if (substr($img->src, 0, 1) != '/') {
                        $img->src = $baseUrl."/".$img->src;
                    } else {
                        $img->src = $baseUrl.$img->src;
                    }
                }
            }
        }

        return $html->innertext;
    }

    function getByCurl($url) {
        $ch =  curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //curl connection time in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //curl execution time in seconds
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    function is_word_exists ($haystack, $needle = [], $offset=0) {
        $haystack = html_entity_decode($haystack);
        
        if (!is_array($needle)) {
            $needle = array($needle);
        }
        
        foreach ($needle as $searchstring) {
            if (stripos($haystack,$searchstring) !== false) return true;
        }
        
        return false;
    }

    function getLinkContent($link, $curl = false)
    {
        if (!$curl) {
         
            $context = stream_context_create(
                array(
                    'ssl' => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                )
            );

            $result = file_get_contents($link, false, $context);
        } else {
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //curl connection time in seconds
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); //curl execution time in seconds
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt(
                $ch,
                CURLOPT_USERAGENT,
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17'
            );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_ENCODING, '');

            $execute = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($error)
                return false;
            $result = $execute;
        }
        return $result;
    }
    function getLinkContents($link)
    {
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    
        $response = curl_exec($ch);
    
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: $error");
        }
    
        // Check HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            curl_close($ch);
            throw new Exception("HTTP Error: Status code $httpCode");
        }
    
        curl_close($ch);
        return $response;
    }
    
    // Example usage
    // try {
    //     $xml = getLinkContents('https://theccf.ca/feed/');
    //     echo '<pre>' . htmlentities($xml) . '</pre>';
    //     $parser = new FeedParser($xml);
    // } catch (Exception $e) {
    //     echo 'Error: ' . $e->getMessage();
    // }
    
?>