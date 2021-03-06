<?php
/**
 * PcGen is a PHP Code Generation support package
 *
 * Copyright 2020 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link <https://kigkonsult.se>
 * Support <https://github.com/iCalcreator/PcGen>
 *
 * This file is part of PcGen.
 *
 * PcGen is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PcGen is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PcGen.  If not, see <https://www.gnu.org/licenses/>.
 */
namespace Kigkonsult\PcGen;

use InvalidArgumentException;

/**
 * Class BaseB
 *
 * Adds variable and body to BaseA
 *
 * @package Kigkonsult\PcGen
 */
abstract class BaseB extends BaseA
{

    /**
     * @var string
     */
    protected static $CODEBLOCKSTART = '{';
    protected static $CODEBLOCKEND   = '}';
    protected static $CLOSECLAUSE    = ';';
    protected static $ERR1           = 'No variable set';
    protected static $STATIC         = 'static';

    /**
     * @var string
     */
    protected $name = null;

    /**
     * @var string[]
     */
    protected $body = [];

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isNameSet() {
        return ( null !== $this->name );
    }

    /**
     * @param string $name
     * @return static
     * @throws InvalidArgumentException
     */
    public function setName( $name ) {
        $this->name = Assert::assertPhpVar( $name );
        return $this;
    }

    /**
     * Return body, all rows has (at least) leading baseIndent
     *
     * @param string $indent
     * @return array
     */
    public function getBody( $indent = null ) {
        $output = [];
        if( empty( $indent )) {
            $indent = self::$SP0;
        }
        foreach( $this->body as $row ) {
            $output[] = ( empty( $row )) ? self::$SP0 : $this->baseIndent . $indent . $row;
        }
        return $output;
    }

    /**
     * @return bool
     */
    public function isBodySet() {
        return ( ! empty( $this->body ));
    }

    /**
     * Set and save body as array, all rows with the same level of of ltrim, all rtrim'd
     *
     * @param string|string[] $body
     * @return static
     */
    public function setBody( ...$body ) {
        $repl = bin2hex( openssl_random_pseudo_bytes( 16 ));
        $tmp  = [];
        foreach( $body as $bodyPart ) {
            if( ! is_array( $bodyPart )) {
                $bodyPart = [ $bodyPart ];
            }
            foreach( $bodyPart as $row ) {
                $tmp[] = ( self::$SP0 == trim( $row )) ? self::$SP0 : rtrim( $row );
            }
        } // end array
        $this->body = [];
        if( 1 == count( $tmp )) {
            $this->body[] = $tmp[0];
        }
        else {
            $lSpaceLen = empty( $tmp[0] )
                ? 0
                : strlen( $tmp[0] ) - strlen( ltrim( $tmp[0] ));
            foreach( $tmp as $row ) {
                if( empty( $row )) {
                    $this->body[] = self::$SP0;
                    continue;
                }
                if( ! empty( $lSpaceLen ) && empty( ltrim( substr( $row, 0, $lSpaceLen )))) {
                    $row = substr( $row, $lSpaceLen );
                }
                $this->body[] = $row;
            }
        }
        $this->body = implode( $repl, $this->body );
        $this->body = rtrim( str_replace( self::$CRLFs, $repl, $this->body ), $this->eol );
        $this->body = explode( $repl, $this->body );
        return $this;
    }

}
