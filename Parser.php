<?php
require_once(__DIR__ . "/lib/includes.php"); // db configuration class

ini_set('max_execution_time', 300);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
class Parser
{
    public $dbObj;
    public $counter = 1;
    public $maxFeeds = 100; //maximum feeds to insert
    function __construct($rss_id = null)
    {

        //get the database obj
        $this->dbObj = new db();
        //if rss_id is null exit the script
        if ($rss_id === null) {
            $this->logMessage("rss_id missing");
            exit;
        }
        //else continue the parsing process
        $rssObj = $this->dbObj->get_rss_info($rss_id);

        //check if rss info available
        if ($rssObj) {
            //check if rss feed available
            if (!empty($rssObj['link'])) {

                $this->logMessage("Rss Feed:" . $rssObj['link']);
                $this->processXmlFeed($rssObj);
            } else {
                //do the scrapping work...
            }
        } else {
            //rss_id not available in database
            //log message and exit
            $this->logMessage("Invalid rss_id");
            exit;
        }
    }

    function processXmlFeed($rssObj)
    {
        $xml = $this->getSourceFeedXml($rssObj['link']);
        $this->logMessage("Feed response bytes: " . strlen($xml));
        $feed = new FeedParser($xml);
        $this->logMessage("Feed parser created");
        $items = $feed->getItems();
        $this->logMessage("Feed items: " . count($items));
        foreach ($items as $item) {
            if ($this->counter > $this->maxFeeds)
                break;

            $link = (is_empty($item->getLink())) ? "" : $item->getLink();
            $title = (is_empty($item->getTitle())) ? "" : cleanString($item->getTitle());
            $guid = (is_empty($item->getGuid())) ? $link : $item->getGuid();

            $this->logMessage("Checking: " . $title);






            //if feed not exists already insert it into db.

            if (!$this->dbObj->feed_exists($title, $guid, $rssObj['rss_id'])) {
                $rawContent = $item->getContent();
                $content = is_empty($rawContent) ? null : $this->cleanContent($rawContent);
                $description = (is_empty($item->getDescription())) ? "" : cleanString($item->getDescription());
                $pubDate = (is_empty($item->getPubDate())) ? "" : $item->getPubDate();
                $issue_date = (is_empty($pubDate)) ? "" : date("Y-m-d H:i:s", strtotime($pubDate));

                $category = (is_empty($item->getCategory())) ? "" : $item->getCategory();
                // ignore video, audio, radio, TV etc category posts
                if (!isCategoryValid_helper($category)) {
                    $this->logMessage("Skipped invalid category: " . $category);
                    continue;
                }


                if ($rssObj['rss_id'] == 5) {
                    // ignore pdfs
                    if (!isCategoryValid_helper($category, ['Publicacione'])) {
                        $this->logMessage("Skipped source category: " . $category);
                        continue;
                    }
                }
                $author = (is_empty($item->getAuthor())) ? "" : cleanString($item->getAuthor());
                $wfwCommentRss = (is_empty($item->wfwCommentRss())) ? null : $item->wfwCommentRss();
                $slashComments = (is_empty($item->slashComments())) ? null : $item->slashComments();
                $comments = (is_empty($item->getComments())) ? null : $item->getComments();
                //check if content is null and can be scrap against link
                if ($content === null) {
                    $function = strtolower(str_replace("-", "_", $rssObj['code']));
                    if (in_array(strtoupper($rssObj['code']), ['LYP', 'LIBERTADYPROGRESO'], true) && !is_empty($description)) {
                        $content = $this->cleanContent($description);
                    } elseif (method_exists($this, $function)) {
                        $content = $this->$function($link);
                        if (is_array($content)) extract($content);
                    } elseif ($rssObj['rss_id'] == 12) {
                        $content = $description;
                        if (!isValidMinWordsLimit($content, 110))
                            continue;
                    } else {
                        $content = null;
                        $this->logMessage("No content in xml feed and also no function is defined to get content");
                    }
                }
                if ($rssObj['rss_id'] == 12) {
                }
                if (!isValidMinWordsLimit($content, 120)) {
                    $this->logMessage("Skipped content under 120 words: " . $title);
                    continue;
                }

                //if content is not null insert else ignore feed
                if ($content !== null) {
                    print_r("<pre>link: " . $link . "</pre>");
                    print_r("<pre>title: " . $title . "</pre>");
                    print_r("<pre>description: " . $description . "</pre>");
                    print_r("<pre>pubDate: " . $pubDate . "</pre>");
                    print_r("<pre>issue_date: " . $issue_date . "</pre>");
                    print_r("<pre>category: " . $category . "</pre>");
                    print_r("<pre>author: " . $author . "</pre>");
                    print_r("<pre>content: " . html_entity_decode($content) . "</pre>");
                    echo "<hr><br>";
                    $this->dbObj->insert_into_db($rssObj['rss_id'], $title, $description, $content, $guid, $pubDate, $issue_date, $category, $link, $author, $comments, $wfwCommentRss, $slashComments);
                    $this->counter++;  //increment counter feed inserted
                }
            } else {
                $this->logMessage("Exist Already:" . $title);
            }
        }
    }

    function getSourceFeedXml($sourceUrl)
    {
        if (strpos($sourceUrl, 'libertadyprogresonline.org') === false && strpos($sourceUrl, 'libertadyprogreso.org') === false) {
            return getLinkContent($sourceUrl, true);
        }

        $feedUrls = [
            'https://www.libertadyprogreso.org/index.php/es/actualidad/economia?format=feed&type=rss',
            'https://www.libertadyprogreso.org/index.php/es/actualidad/politica?format=feed&type=rss',
            'https://www.libertadyprogreso.org/index.php/es/actualidad/educacion?format=feed&type=rss',
            'https://www.libertadyprogreso.org/index.php/es/actualidad/internacionales?format=feed&type=rss',
            'https://www.libertadyprogreso.org/index.php/es/actualidad/gacetilla-de-prensa?format=feed&type=rss',
            'https://www.libertadyprogreso.org/index.php/es/datos/opinion-y-debate-de-ideas?format=feed&type=rss',
            'https://www.libertadyprogreso.org/index.php/es/propuestas/investigaciones-de-la-fundacion?format=feed&type=rss',
            'https://www.libertadyprogreso.org/index.php/es/propuestas/politicas-publicas-2025?format=feed&type=rss'
        ];

        $merged = new DOMDocument('1.0', 'UTF-8');
        $merged->formatOutput = true;
        $rss = $merged->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $channel = $merged->createElement('channel');
        $channel->appendChild($merged->createElement('title', 'Libertad y Progreso Argentina'));
        $channel->appendChild($merged->createElement('link', 'https://www.libertadyprogreso.org/'));
        $channel->appendChild($merged->createElement('description', 'Combined Libertad y Progreso feeds'));
        $rss->appendChild($channel);
        $merged->appendChild($rss);

        $items = [];
        foreach ($feedUrls as $feedUrl) {
            $feedXml = getLinkContent($feedUrl, true);
            $feedDocument = new DOMDocument();
            if (!$feedXml || !@$feedDocument->loadXML($feedXml)) {
                $this->logMessage('Feed unavailable: ' . $feedUrl);
                continue;
            }

            foreach ($feedDocument->getElementsByTagName('item') as $item) {
                if (!($item instanceof DOMElement)) {
                    continue;
                }

                $guid = '';
                foreach (['guid', 'link'] as $tagName) {
                    $nodes = $item->getElementsByTagName($tagName);
                    if ($nodes->length > 0 && trim($nodes->item(0)->nodeValue) !== '') {
                        $guid = trim($nodes->item(0)->nodeValue);
                        break;
                    }
                }
                if ($guid !== '') {
                    $items[$guid] = $item;
                }
            }
        }

        uasort($items, function ($first, $second) {
            $firstDate = strtotime($first->getElementsByTagName('pubDate')->item(0)->nodeValue ?? '') ?: 0;
            $secondDate = strtotime($second->getElementsByTagName('pubDate')->item(0)->nodeValue ?? '') ?: 0;
            return $secondDate <=> $firstDate;
        });

        foreach ($items as $item) {
            $channel->appendChild($merged->importNode($item, true));
        }

        $this->logMessage('Combined Argentina feed items: ' . count($items));
        return $merged->saveXML();
    }

    function lydc($link)
    {
        $html = str_get_html(getLinkContent($link, false));
        if ($html->find('div.entry-content', 0)) {
            $data = $html->find('div.entry-content', 0);

            if ($data->find("script")) {
                foreach ($data->find('script') as $key => $script) {
                    $script->outertext = "";
                }
            }

            if ($data->find("style")) {
                foreach ($data->find('style') as $key => $script) {
                    $script->outertext = "";
                }
            }

            if ($data->find("noscript")) {
                foreach ($data->find('noscript') as $key => $script) {
                    $script->outertext = "";
                }
            }
            if ($data->find("img")) {
                foreach ($data->find('img') as $key => $script)
                    $script->outertext = "";
            }
            $filtrer_tags = ['p', 'h3'];
            for ($i = 0; $i < count($filtrer_tags); $i++)
                if ($div = $data->find($filtrer_tags[$i]))
                    foreach ($div as  $element)
                        if (begnWith($element->plaintext, "VER PROGRAMA") || begnWith($element->plaintext, "El formato") || begnWith($element->plaintext, "VER PRESENTACIÓN") || begnWith($element->plaintext, "INSCRIPCIONES") || begnWith($element->plaintext, "Columna de") || begnWith($element->plaintext, "https:") || begnWith($element->plaintext, "www."))
                            $element->outertext = "";


            $content = cleanString($data->innertext);
        } else {
            $content = null;
        }
        return $content;
    }
    function cleanContent($content)
    {


        $content = str_get_html($content);

        if (!empty($content)) {




            $default_checks = ['script', 'noscript', 'style', 'iframe', '.twitter-tweet', '.instagram-media', 'a'];
            for ($i = 0; $i < count($default_checks); $i++) {
                if ($div = $content->find($default_checks[$i])) {
                    foreach ($div as  $element) {
                        $element->outertext = "";
                    }
                }
            }

            $checks = ['.aside__title'];
            for ($i = 0; $i < count($checks); $i++) {
                if ($div = $content->find($checks[$i])) {
                    foreach ($div as  $element) {
                        $element->outertext = "";
                    }
                }
            }

            foreach ($content->find('p') as $element) {
                if ($this->begnWith($element->plaintext, "The post Sobre la suba del dólar:")) {
                    $element->outertext = "";
                }
            }

            return trim($content->innertext);
        }
    }


    function begnWith($str, $begnString)
    {
        $len = strlen($begnString);
        return (substr(strtolower($str), 0, $len) === strtolower($begnString));
    }

    function cari($link)
    {
        if (strpos($link, 'newsletter') !== false) {
            return null;
        }

        $html = file_get_html($link);
        $params = explode("#", $link);
        $id = $params[1];

        if ($html->find("#$id", 0)) {
            $anchor = $html->find("#$id", 0);
            //process the date part
            $dateSegment = explode("-", preg_replace('/[^0-9-]+/', '', $anchor->name));
            $pubDate = date("Y") . "-" . str_pad($dateSegment[1], 2, "0") . "-" . str_pad($dateSegment[0], 2, "0");
            if (strtotime(date("Y-m-d")) < strtotime($pubDate)) {
                $pubDate = date("Y-m-d H:i:s", strtotime("-1 year", strtotime($pubDate)));
                $issue_date = $pubDate;
            } else {
                $pubDate = $pubDate . " 00:00:00";
                $issue_date = $pubDate;
            }
            //end date part

            $content = $anchor->parent()->parent();

            if ($content->find(".titulo")) {
                $content->find(".titulo", 0)->outertext = "";
            }

            if ($content->find(".fecha_hora")) {
                $content->find(".fecha_hora", 0)->outertext = "";
            }

            $content = cleanString($content->innertext);
            $response = array("content" => $content, "pubDate" => $pubDate, "issue_date" => $issue_date);
        } else {
            $response = null;
        }

        return $response;
    }

    function capp($link)
    {
        $html = file_get_html($link);
        if ($html->find('div.blogpost-body', 0)) {
            $data = $html->find('div.blogpost-body', 0);

            /* if($data->find("script")){ foreach($data->find('script') as $key => $script) { $script->outertext = ""; } }
            if($data->find("noscript")){ foreach($data->find('noscript') as $key => $script) { $script->outertext = ""; } } */

            $default_checks = ['script', 'noscript', 'style', 'iframe', '.twitter-tweet', 'figure', 'img', 'picture', 'video'];
            for ($i = 0; $i < count($default_checks); $i++) {
                if ($div = $data->find($default_checks[$i]))
                    foreach ($div as  $element)
                        $element->outertext = "";
            }


            $content = cleanString($data->innertext);
        } else
            $content = null;

        return $content;
    }

    function iplp($link)
    {

        $html = str_get_html(getLinkContent($link, true));
        if ($data = $html->find('div.entry-content', 0)) {
            if ($data->find("script")) {
                foreach ($data->find('script') as $key => $script) {
                    $script->outertext = "";
                }
            }

            if ($data->find("noscript")) {
                foreach ($data->find('noscript') as $key => $script) {
                    $script->outertext = "";
                }
            }
            if ($data->find("div.watch-action")) {
                $data->find('div.watch-action', 0)->outertext = "";
            }
            if ($data->find("div.wti-clear")) {
                $data->find('div.wti-clear', 0)->outertext = "";
            }
            if ($data->find("div.yarpp-related")) {
                $data->find('div.yarpp-related', 0)->outertext = "";
            }
            if ($data->find(".bawpvc-ajax-counter")) {
                $data->find('.bawpvc-ajax-counter', 0)->outertext = "";
            }
            $content = cleanString($data->innertext);
        } else {
            $content = null;
        }
        return $content;
    }

    function prime($link)
    {
        $html =  str_get_html(getLinkContent($link, true));

        // if($html->find('article div.bdaia-post-content',0)){
        if ($html->find('.entry-content', 0)) {
            $data = $html->find('.entry-content', 0);
            if ($data->find("script")) {
                foreach ($data->find('script') as $key => $script) {
                    $script->outertext = "";
                }
            }

            if ($data->find("noscript")) {
                foreach ($data->find('noscript') as $key => $script) {
                    $script->outertext = "";
                }
            }

            if ($data->find("style")) {
                foreach ($data->find('style') as $key => $script) {
                    $script->outertext = "";
                }
            }

            if ($data->find(".w3eden", 0)) {
                return null; // in case of pdf doc
            }
            if ($data->find('p', 0))
                foreach ($data->find('p') as $element)
                    if (begnWith($element->plaintext, "Click below") || begnWith($element->plaintext, "For media inquiries") || begnWith($element->plaintext, "[CONTINUED") || begnWith($element->plaintext, "To read more") || begnWith($element->plaintext, "For inquiries") || begnWith($element->plaintext, "https:") || begnWith($element->plaintext, "www."))
                        $element->outertext = "";


            $content = cleanStringV2($data->innertext);
        } else {
            $content = null;
        }
        return $content;
    }

    function logMessage($msg = "unknown error")
    {

        echo "<h5>Message: " . $msg . "</h5><br>";
    }

    function getByCurl($url)
    {
        $ch =  curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //curl connection time in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //curl execution time in seconds
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}

function begnWith($str, $begnString)
{
    $len = strlen($begnString);
    return (substr(strtolower($str), 0, $len) === strtolower($begnString));
}
