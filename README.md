# ott2gml
Export subtrees from Open Tree taxonomy in GML format

Simple PHP code to extract a subtree from OTT and output it in GML format.

## Get OTT

Get OTT from https://devtree.opentreeoflife.org/about/taxonomy-version/ott2.9

## Create and populate MySQL database
	
```
CREATE TABLE `taxonomy` (
  `uid` int(11) unsigned NOT NULL,
  `parent_uid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `rank` varchar(64) DEFAULT NULL,
  `sourceinfo` varchar(255) DEFAULT NULL,
  `uniqname` varchar(255) DEFAULT NULL,
  `flags` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent_uid` (`parent_uid`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
```


Load taxonomy.tsv into MySQL. File is tab and pipe delimited (sigh).



```
LOAD DATA INFILE ‘/Users/rpage/taxonomy.tsv’ 
INTO TABLE taxonomy 
FIELDS TERMINATED BY ‘\t|\t’ 
LINES TERMINATED BY ‘\t|\t\n’
IGNORE 1 LINES
(uid,parent_uid,name,rank,sourceinfo,uniqname,flags);
```


