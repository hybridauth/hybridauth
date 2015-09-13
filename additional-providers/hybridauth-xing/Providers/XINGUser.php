<?php

/**
 * XingUser - basic XING user profile
 *
 * This is based on the standard Hybrid_User_Contact with some more specific fields
 */
class XingUser extends Hybrid_User_Contact
{

    // maps Hybrid_User_Contact to its relative XING field ids
    public static $xingUser_xingapi_fields_map = array(
        'identifier' => 'id',
        // priority ordered, they are parsed in order. If there is nothing also in web_profiles/other, it takes any web_profiles/ existing
        'webSiteURL' => array('web_profiles/homepage', 'web_profiles/blog', 'web_profiles/other', 'web_profiles/*'),
        'profileURL' => 'permalink',
        // photo_urls is a special case and is using some custom logic
        'photoURL' => array( 'photo_urls/' ),
        'displayName' => 'display_name',
        'description' => 'interests',
        'email' => 'active_email',
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'employmentStatus' => 'employment_status',
        'gender' => 'gender',
    );

    // extra attributes that are not included in the Hybrid_User_Contact
    public $firstName = null;
    public $lastName = null;
    public $employmentStatus = null;
    // gender values seems not to be reliable
    public $gender = null;

    /**
     * XINGUser constructor.
     *
     * Create a XING user using with the data coming from the API response
     * @param  stdClass $apiResponse the response coming from XING API request
     * @param string $pictureSize requested size of picture to return
     * @throws Exception
     */
    public function __construct($apiResponse, $pictureSize = null)
    {
        foreach (self::$xingUser_xingapi_fields_map as $classFieldName => $apiFieldName) {
            if (!in_array($classFieldName, array_keys(get_object_vars($this)))) {
                throw new Exception("Cannot find class property [ $classFieldName ]");
            }
            if (is_array($apiFieldName)) {
                // if there are multiple elements, there is a preference order in which they are assigned
                // TODO at the moment just 2 levels but should be recursive to parse also more complex nested fields
                foreach ($apiFieldName as $apiFieldNameItem) {
                    list ($apiFieldNameItemParent, $apiFieldNameItemChild) = explode('/', $apiFieldNameItem);
                    if (!strpos($apiFieldNameItem, '/')) {
                        throw new Exception("Invalid nested property defined [ $classFieldName => $apiFieldNameItem ] ");
                    }

                    // This allows to specify which type of picture to request
                    if ($apiFieldNameItemParent === 'photo_urls') {
                        $apiFieldNameItemChild = XingUserPicureSize::getImageType( $pictureSize );
                    }

                    if (property_exists($apiResponse, $apiFieldNameItemParent)) {
                        if (($apiFieldNameItemChild === '*') && (count(get_object_vars($apiResponse->$apiFieldNameItemParent)) > 0)) {
                            // anything is valid then
                            foreach (array_values(get_object_vars($apiResponse->$apiFieldNameItemParent)) as $itemChildValue) {
                                if ($itemChildValue != null) {
                                    $this->$classFieldName = $itemChildValue;
                                    break;
                                }
                            }
                        } else {
                            if (property_exists($apiResponse->$apiFieldNameItemParent, $apiFieldNameItemChild)) {
                                $this->$classFieldName = $apiResponse->$apiFieldNameItemParent->$apiFieldNameItemChild;
                                // in case we have multiple elements such as in web_profiles/ , we take the fist match
                                break;
                            }
                        }
                    }
                }
            } else {
                // simple property
                $this->$classFieldName = $apiResponse->$apiFieldName;
            }
        }
    }

    public static function getApiRequestFields()
    {
        $xingApiFields = array_values(self::$xingUser_xingapi_fields_map);
        $apiFields = array();
        foreach ($xingApiFields as $xingApiField) {
            if (is_array( $xingApiField )) {
                // nested property
                $tmp = explode( '/', $xingApiField[ 0 ] );
                $xingApiField = $tmp[ 0 ];
            }

            $apiFields[] = $xingApiField;
        }

        return implode(',', $apiFields);
    }
}
