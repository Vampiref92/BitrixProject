<?php
if(!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])){
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$replaceList = [
    'RU' => [
        'TYPE_REF' => '\\GlcGroup\\Enum\\IblockType',
        'CODE_REF' => '\\GlcGroup\\Enum\\IblockCode',
        'ITEMS'    => [
            1 => 'SLIDER',
            2 => 'ADVANTAGES_MAIN',
            3 => 'SERVICES_MAIN',
            4 => 'DOCUMENTS',
            5 => 'NEWS',
            6 => 'REVIEWS',
            7 => 'LICENSES',
            8 => 'VACANCIES',
            9 => 'PARTNERS',
            10 => 'FAQ',
            11 => 'COMPANY_HISTORY',
            12 => 'CATALOG',
            13 => 'CATALOG_COMMENTS',
            14 => 'ORDERS',
            15 => 'STAFF',
            16 => 'ARTICLES',
            17 => 'COMMENTS_ARTICLES',
            18 => 'OUR_PROJECTS',
            19 => 'SERVICES',
            20 => 'STOCKS',
            21 => 'PHOTO_ABOUT_COMPANY_MAIN',
            22 => 'PHOTO_GALLERY',
            23 => 'BANNER_CATALOG',
            24 => 'BANNER_SERVICES',
            25 => 'BANNER_PROJECTS',
            26 => 'CONTENT_FEATURES_COLLECTION',
        ],
    ],
];

$dir = new \Bitrix\Main\IO\Directory($_SERVER['DOCUMENT_ROOT']);
recursiveChange($dir, $replaceList);

function recursiveChange(\Bitrix\Main\IO\FileSystemEntry $children, $replaceList){
    if($children->isDirectory()){
        /** @var \Bitrix\Main\IO\Directory $children */
        foreach ($children->getChildren() as $child) {
            recursiveChange($child, $replaceList);
        }
    } else {
        /** @var \Bitrix\Main\IO\File $children */
        if($children->isFile()){
            $data = $children->getContents();
            if(!empty($data)) {
                foreach ($replaceList as $type => $repl) {
                    foreach ($repl['ITEMS'] as $iblockId => $codeInRef) {
                        $data = \preg_replace('/([\'"]IBLOCK_?ID[\'"]\s*=>?\s*)[\'"]?'.$iblockId.'[\'"]?/im',
                            "$1 \\Vf92\\BitrixUtils\\Iblock\\IblockHelper::getIblockId(".$repl['TYPE_REF']."::".$type.", ".$repl['TYPE_REF']."::".$codeInRef.")",
                            $data);
                    }
                }

                if(!empty($data)) {
                    $children->putContents($data);
                }
            }
        }
    }
}