# rkiCovid

* Covid Incidence Widget for MODX Revolution.
* gets Data by API from https://api.corona-zahlen.org/docs/ - please, consider supporting him by donation, if you use that widget!
* result gets cached for given time, by default one hour
* Show current week incidence of a given district.
* Shows incidence history of configurable past days.
* Can display content, depending on the current incidence value.
* highly configurable

![rkiCovid](https://user-images.githubusercontent.com/148223/110542752-c1343b00-8129-11eb-88a6-4d1275bbdd23.png)

## Installation

Install Transport Package by MODX package manager

## Usage

simple:
```
[[!rkiCovid? &district=`09274`]]
```

with content depending on incidence value:
```
[[!rkiCovid? &district=`09677` &rangeChunksTpl=`rkiCovidRangeChunksTpl`]]

<div class="incidence-content">
[[!+rkiCovidRangeText_09677]]
</div>
```
rkiCovidRangeChunksTpl:
```
[
{"min":0,"max":35,"chunk":"rkiCovidExample35"},
{"min":35,"max":50,"chunk":"rkiCovidExample50"},
{"min":50,"max":100,"chunk":"rkiCovidExample100"},
{"min":100,"max":100000,"chunk":"rkiCovidExampleX"}
]
```

advanced:
```
[[!rkiCovid? 
&district=`09274`
&days=`7`
&colorTpl = `rkiCovidColorTpl`
&districtTpl = `rkiCovidDistrictTpl`
&historyTpl = `rkiCovidHistoryTpl`
&wrapperTpl = `rkiCovidWrapperTpl`
&rangeTextPlaceholder = `rkiCovidRangeText_09274`
]]
```
This are the default chunks. Please don't modify them, but create your own chunks, if you need modifications!
Leave the tpl - properties empty to get all possible placeholders as printed array.




