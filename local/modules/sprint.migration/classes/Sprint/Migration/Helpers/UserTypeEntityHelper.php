<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class UserTypeEntityHelper extends Helper
{
    public function addUserTypeEntitiesIfNotExists($entityId, array $fields) {
        foreach ($fields as $field) {
            $this->addUserTypeEntityIfNotExists($entityId, $field["FIELD_NAME"], $field);
        }
    }

    public function deleteUserTypeEntitiesIfExists($entityId, array $fields) {
        foreach ($fields as $fieldName) {
            $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
        }
    }

    public function addUserTypeEntityIfNotExists($entityId, $fieldName, $fields) {
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if ($item) {
            return $item['ID'];
        }

        $default = array(
            "ENTITY_ID" => '',
            "FIELD_NAME" => '',
            "USER_TYPE_ID" => '',
            "XML_ID" => '',
            "SORT" => 500,
            "MULTIPLE" => 'N',
            "MANDATORY" => 'N',
            "SHOW_FILTER" => 'I',
            "SHOW_IN_LIST" => '',
            "EDIT_IN_LIST" => '',
            "IS_SEARCHABLE" => '',
            "SETTINGS" => array(),
            "EDIT_FORM_LABEL" => array('ru' => '', 'en' => ''),
            "LIST_COLUMN_LABEL" => array('ru' => '', 'en' => ''),
            "LIST_FILTER_LABEL" => array('ru' => '', 'en' => ''),
            "ERROR_MESSAGE" => '',
            "HELP_MESSAGE" => '',
        );

        $fields = array_replace_recursive($default, $fields);
        $fields['FIELD_NAME'] = $fieldName;
        $fields['ENTITY_ID'] = $entityId;

        $enums = array();
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        $obUserField = new \CUserTypeEntity;
        $userFieldId = $obUserField->Add($fields);

        $enumsCreated = true;
        if ($userFieldId && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($userFieldId, $enums);
        }

        if ($userFieldId && $enumsCreated) {
            return $userFieldId;
        }

        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'UserType %s not added', $fieldName);
        }
    }

    public function updateUserTypeEntityIfExists($entityId, $fieldName, $fields) {
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (!$item) {
            return false;
        }

        $fields['FIELD_NAME'] = $fieldName;
        $fields['ENTITY_ID'] = $entityId;

        $enums = array();
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        $entity = new \CUserTypeEntity;
        $userFieldUpdated = $entity->Update($item['ID'], $fields);

        $enumsCreated = true;
        if ($userFieldUpdated && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($item['ID'], $enums);
        }

        if ($userFieldUpdated && $enumsCreated) {
            return $userFieldUpdated;
        }

        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'UserType %s not updated', $fieldName);
        }
    }

    public function getUserTypeEntities($entityId) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => $entityId));
        $result = array();
        while ($item = $dbRes->Fetch()) {
            $result[] = $this->getUserTypeEntityById($item['ID']);
        }
        return $result;
    }

    public function getUserTypeEntity($entityId, $fieldName) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = \CUserTypeEntity::GetList(array(), array(
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName
        ))->Fetch();

        return (!empty($item)) ? $this->getUserTypeEntityById($item['ID']) : false;
    }

    public function setUserTypeEntityEnumValues($fieldId, $newenums) {
        $newenums = is_array($newenums) ? $newenums : array();
        $oldenums = $this->getEnumValues($fieldId, true);

        $index = 0;

        $updates = array();
        foreach ($oldenums as $oldenum) {
            $newenum = $this->searchEnum($oldenum, $newenums);
            if ($newenum) {
                $updates[$oldenum['ID']] = $newenum;
            } else {
                $oldenum['DEL'] = 'Y';
                $updates[$oldenum['ID']] = $oldenum;
            }
        }

        foreach ($newenums as $newenum) {
            $oldenum = $this->searchEnum($newenum, $oldenums);
            if ($oldenum) {
                $updates[$oldenum['ID']] = $newenum;
            } else {
                $updates['n' . $index++] = $newenum;
            }
        }

        $obEnum = new \CUserFieldEnum();
        return $obEnum->SetEnumValues($fieldId, $updates);

    }

    public function deleteUserTypeEntityIfExists($entityId, $fieldName) {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (!$item) {
            return false;
        }

        $entity = new \CUserTypeEntity();
        if ($entity->Delete($item['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'UserType not deleted');
    }

    /* @deprecated */
    public function deleteUserTypeEntity($entityId, $fieldName) {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }

    protected function getUserTypeEntityById($fieldId) {
        $item = \CUserTypeEntity::GetByID($fieldId);

        if ($item && $item['USER_TYPE_ID'] == 'enumeration') {
            $item['ENUM_VALUES'] = $this->getEnumValues($fieldId, false);
        }

        return $item;
    }

    protected function getEnumValues($fieldId, $full = false) {
        $obEnum = new \CUserFieldEnum;
        $dbres = $obEnum->GetList(array(), array("USER_FIELD_ID" => $fieldId));

        $result = array();
        while ($enum = $dbres->Fetch()) {
            if ($full) {
                $result[] = $enum;
            } else {
                $result[] = array(
                    'VALUE' => $enum['VALUE'],
                    'DEF' => $enum['DEF'],
                    'SORT' => $enum['SORT'],
                    'XML_ID' => $enum['XML_ID'],
                );
            }
        }

        return $result;
    }

    protected function searchEnum($enum, $haystack = array()) {
        foreach ($haystack as $item) {
            if (!empty($item['XML_ID']) && $item['XML_ID'] == $enum['XML_ID']) {
                return $item;
            }
        }
        return false;
    }

}
