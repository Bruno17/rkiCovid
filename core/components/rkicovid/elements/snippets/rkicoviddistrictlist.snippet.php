<?php
$cacheKey = $modx->getOption('cacheKey', scriptProperties, 'rkicovid');
$cacheHandler = $modx->getOption('cacheHandler', $scriptProperties, 'xPDOFileCache');
$cacheExpires = (integer) $modx->getOption('cacheExpires', $scriptProperties, 3600);

$cacheOptions = array(
    xPDO::OPT_CACHE_KEY => $cacheKey,
    xPDO::OPT_CACHE_HANDLER => $cacheHandler,
    xPDO::OPT_CACHE_EXPIRES => $cacheExpires,
);
if ($modx->getCacheManager()) {
    $district_array = $modx->cacheManager->get('districts', $cacheOptions);
}

if ($district_array){

}  else {
    $district_data = file_get_contents('https://api.corona-zahlen.org/districts');
    if ($district_data) {
        $district_array = json_decode($district_data,1);
        if (is_array($district_array) && isset($district_array['data'])){
            if ($modx->getCacheManager()) {
                $modx->cacheManager->set('districts', $district_array, $cacheExpires, $cacheOptions);
            }
        }    
    }
}

$output = [];
if (is_array($district_array) && isset($district_array['data'])){
    if (!empty($districtTpl) && is_array($district_array['data']) && is_array($district_array['meta'])){
        $properties = array_merge($district_array['data'],$district_array['meta']);
        $output['district_output'] = $modx->getChunk($districtTpl,$properties);    
    }  else {
        $output['district_output'] = '<pre>' . print_r($district_array,true) . '</pre>';
    }
} else {
    $output['district_output'] = 'Daten für Landkreis konnten nicht geladen werden';
}

if (!empty($wrapperTpl)){
    return $modx->getChunk($wrapperTpl,$output);    
}
return implode("\n",$output);