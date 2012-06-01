<?php
/**
 * Factory for eZContentStagingField types
 * Uses subclasses according to attribute data_type_string, defaults to main eZContentStagingField class
 *
 * Set data_type_string to PHP class mapping in contentstagingtarget.ini
 *
 * Subclasses need to encode data in constructor and to expose a static decodeValue() method
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */
class eZContentStagingFieldFactory
{
    /**
     * Creates an instance of a eZContentStagingField subclass according to $attribute datatype
     * @param eZContentObjectAttribute $attribute
     * @param string $locale
     * @param eZContentStagingRemoteIdGenerator $ridGenerator
     * @return eZContentStagingField
     */
    static public function getField( eZContentObjectAttribute $attribute, $locale, $ridGenerator )
    {
        if( $class = self::getSerializerClass( $attribute->attribute( 'data_type_string' ) ) )
        {
            try{
                return new $class( $attribute, $locale, $ridGenerator );
            }
            catch( Exception $e )
            {
                eZDebug::writeError( 'Could not create an instance of class ' . $class . ' : ' . $e->getMessage(), __METHOD__ );
            }
        }

        //falling back to default field class
        return new eZContentStagingField( $attribute, $locale, $ridGenerator );
    }

    /**
     * Decodes $value uses the right eZContentStagingField subclass according to $attribute's data_type_string
     * @param eZContentObjectAttribute $attribute
     * @param array $value
     * @return bool indicates decoding success
     */
    static public function decodeValue( $attribute, $value )
    {
        if( $class = self::getSerializerClass( $attribute->attribute( 'data_type_string' ) ) )
        {
            try{
                return call_user_func_array( array( $class, 'decodeValue' ),
                                      array( $attribute, $locale, $ridGenerator ) );
            }
            catch( Exception $e )
            {
                eZDebug::writeError( 'Could not create an instance of class ' . $class . ' : ' . $e->getMessage(), __METHOD__ );
            }
        }

        //falling back to default field class
        return eZContentStagingField::decodeValue( $attribute, $value );
    }

    /**
     * Gets the eZContentStagingField subclass name for $datatypeString from contentstagingtarget.ini
     * Exeistence and type checking included
     * @param string $datatypeString
     * @return string|bool	Class name or false if not found
     */
    static private function getSerializerClass( $datatypeString )
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $datatypeSerializers = $ini->variable( 'FieldFactory', 'DatatypeSerializers' );

        if( isset( $datatypeSerializers[ $datatypeString ] ) )
        {
            $class = $datatypeSerializers[ $datatypeString ];
            if( class_exists( $class ) )
            {
                if( !in_array( 'eZContentStagingField', class_parents( $class ) ) )
                {
                    eZDebug::writeError( 'Class ' . $class . ' is not a valid eZContentStagingField.', __METHOD__ );
                    return false;
                }

                return $class;
            }
            else
            {
                eZDebug::writeError( 'Class ' . $class . ' could not be found. Maybe you need to regenerate autoloads.', __METHOD__ );
            }
        }

        return false;
    }
}