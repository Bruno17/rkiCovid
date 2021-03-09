<?php
$district = $modx->getOption('district',$scriptProperties,'09677');
$days = $modx->getOption('days',$scriptProperties,'7');
$colorTpl = $modx->getOption('colorTpl',$scriptProperties,'rkiCovidColorTpl');
$districtTpl = $modx->getOption('districtTpl',$scriptProperties,'rkiCovidDistrictTpl');
$historyTpl = $modx->getOption('historyTpl',$scriptProperties,'rkiCovidHistoryTpl');
$wrapperTpl = $modx->getOption('wrapperTpl',$scriptProperties,'rkiCovidWrapperTpl');
$rangeChunksTpl = $modx->getOption('rangeChunksTpl',$scriptProperties,'');
$rangeTextPlaceholder = $modx->getOption('rangeTextPlaceholder',$scriptProperties,'rkiCovidRangeText_' . $district);

$cacheKey = $modx->getOption('cacheKey', scriptProperties, 'rkicovid');
$cacheHandler = $modx->getOption('cacheHandler', $scriptProperties, 'xPDOFileCache');
$cacheExpires = (integer) $modx->getOption('cacheExpires', $scriptProperties, 3600);

$weekIncidence = 0;

if (!function_exists('rkiCovid_getIncidenceColor')){
    function rkiCovid_getIncidenceColor($incidence, $colors_array){
        foreach ($colors_array as $color){
            if ($incidence >= $color['min'] && $incidence <= $color['max']){
                return $color['color'];
            }
        }
    }
}

$cacheOptions = array(
    xPDO::OPT_CACHE_KEY => $cacheKey,
    xPDO::OPT_CACHE_HANDLER => $cacheHandler,
    xPDO::OPT_CACHE_EXPIRES => $cacheExpires,
);
if ($modx->getCacheManager()) {
    $district_array = $modx->cacheManager->get('district_' . $district, $cacheOptions);
    $history_array = $modx->cacheManager->get('history_' . $district . '_' . $days, $cacheOptions);
    $colors_array = $modx->cacheManager->get('colors', $cacheOptions);
}

if ($colors_array){
    //echo 'colors found';
}  else {
    //echo 'colors not found';
    $fetchUrl = 'https://api.corona-zahlen.org/map/districts/legend';
    $colors_data = file_get_contents($fetchUrl);
    $colors_array = json_decode($colors_data,1);
    if (is_array($colors_array) && isset($colors_array['incidentRanges'])){
        $colors_array['meta']['fetchUrl'] = $fetchUrl;
        if ($modx->getCacheManager()) {
            $modx->cacheManager->set('colors', $colors_array, $cacheExpires, $cacheOptions);
        }
    }    
}

if ($district_array){

}  else {
    $fetchUrl = 'https://api.corona-zahlen.org/districts/' . $district;
    $district_data = file_get_contents($fetchUrl);
    if ($district_data) {
        $district_array = json_decode($district_data,1);
        if (is_array($district_array) && isset($district_array['data'])){
            $district_array['meta']['fetchUrl'] = $fetchUrl;
            if ($modx->getCacheManager()) {
                $modx->cacheManager->set('district_' . $district, $district_array, $cacheExpires, $cacheOptions);
            }
        }    
    }
}

if ($history_array){
    
}  else {
    $fetchUrl = 'https://api.corona-zahlen.org/districts/' . $district . '/history/incidence/' . $days;
    $history_data = file_get_contents($fetchUrl);
    $history_array = json_decode($history_data,1);
    if (is_array($history_array) && isset($history_array['data']) && isset($history_array['data'][$district]['history'])){
        $history_array['meta']['fetchUrl'] = $fetchUrl;
        $max = 0;
        $min = 100000;
        foreach ($history_array['data'][$district]['history'] as $key => $item){
            if ($item['weekIncidence'] < $min){
                $min = $item['weekIncidence'];    
            }
            if ($item['weekIncidence'] > $max){
                $max = $item['weekIncidence'];    
            }             
        }
        $maxpercent = 100;

        $history = [];
        foreach ($history_array['data'][$district]['history'] as $key => $item){
            $item['percent'] = round(($maxpercent * $item['weekIncidence']) / $max);
            $item['weekIncidence'] = round($item['weekIncidence'],1);
            
            $item['color'] = rkiCovid_getIncidenceColor($item['weekIncidence'], $colors_array['incidentRanges']);            
            
            $history_array['data'][$district]['history'][$key] = $item;            
            //$history[] = $modx->getChunk($historyTpl,$item);    
        }
        for ($i=0;$i<$days;$i++){
            if (!isset($history_array['data'][$district]['history'][$i])){
                $item = [];
                $daysback = $days - $i;
                $item['date'] = strftime('%Y-%m-%d 00:00:00',strtotime('-' . $daysback . ' day'));
                $item['weekIncidence'] = 'k.a.';
                $item['color'] = '#ffffff';
                $item['percent'] = 100;
                $history_array['data'][$district]['history'][$i] = $item;
            }
        }
        
        
        if ($modx->getCacheManager()) {
            $modx->cacheManager->set('history_' . $district . '_' . $days, $history_array, $cacheExpires, $cacheOptions);
        }    
    }
}

$output = [];
$properties = [];
if (is_array($district_array) && isset($district_array['data'][$district])){
    if (is_array($district_array['data'][$district]) && is_array($district_array['meta'])){
        $properties = array_merge($district_array['data'][$district],$district_array['meta']);
        $weekIncidence = $properties['weekIncidence'] = round($properties['weekIncidence'],1);
    }
    if (!empty($districtTpl)){        
        $output['district_output'] = $modx->getChunk($districtTpl,$properties);    
    }  else {
        $output['district_output'] = '<pre>' . print_r($district_array,true) . '</pre>';
    }
} else {
    $output['district_output'] = 'Daten für Landkreis konnten nicht geladen werden';
}

if (is_array($history_array) && isset($history_array['data'][$district]['history'])){
    if (!empty($historyTpl)){
        $history = [];
        foreach ($history_array['data'][$district]['history'] as $item){
            $history[] = $modx->getChunk($historyTpl,$item);    
        }
        $output['history_output'] = implode("\n",$history);
    }  else {    
        $output['history_output'] = '<pre>' . print_r($history_array,true) . '</pre>';
    }
} else {
    $output['history_output'] = 'Historydaten konnten nicht geladen werden';
}

if (is_array($colors_array) && isset($colors_array['incidentRanges'])){
    if (!empty($colorTpl)){
        $colors_output = [];
        foreach ($colors_array['incidentRanges'] as $color) {
            $colors_output[] = $modx->getChunk($colorTpl,$color); 
        } 
        $output['ranges_output'] = implode("\n",$colors_output);
    } else {
        $output['ranges_output'] = '<pre>' . print_r($colors_array,true) . '</pre>';    
    }
} else {
    $output['ranges_output'] = 'Farbdaten konnten nicht geladen werden';
}

if (!empty($rangeChunksTpl)){
    $range_chunks = $modx->getChunk($rangeChunksTpl,[]);
    $range_chunks_array = json_decode($range_chunks,true);
    if (is_array($range_chunks_array)){
        foreach ($range_chunks_array as $range){
            if (isset($range['min']) && isset($range['max']) && $weekIncidence >= $range['min'] && $weekIncidence < $range['max']){
                $properties = array_merge($properties,$range);
                $modx->setPlaceholder($rangeTextPlaceholder,$modx->getChunk($range['chunk'],$properties));    
            }    
        }
    }
    
}

if (!empty($wrapperTpl)){
    return $modx->getChunk($wrapperTpl,$output);    
}
return implode("\n",$output);