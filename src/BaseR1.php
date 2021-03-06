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
use RuntimeException;

abstract class BaseR1 extends BaseA
{

    /**
     * @var string
     */
    protected static $END = ';';

    /**
     * Scalar value
     *
     * @var bool|int|float|string
     */
    protected $fixedSourceValue = null;

    /**
     * @var EntityMgr
     */
    protected $source = null;

    /**
     * @var ChainInvokeMgr
     */
    protected $fcnInvoke = null;

    /**
     * @return array
     * @throws RuntimeException
     */
    protected function getRenderedSource() {
        static $ERR = 'No source set';
        $code = [];
        switch( true ) {
            case $this->isFixedSourceValueSet() :
                $code[] = $this->getFixedSourceValue( false );
                break;
            case $this->isSourceSet() :
                $code[] = rtrim( $this->getSource()->toString());
                break;
            case $this->isFcnInvokeSet() :
                $code = $this->getFcnInvoke()->toArray();
                break;
            default :
                throw new RuntimeException( $ERR );
                break;
        }
        return $code;
    }
    /**
     * @param bool $strict  false returns fixedSourceValue as string
     * @return bool|float|int|string
     */
    public function getFixedSourceValue( $strict = true ) {
        return $strict ? $this->fixedSourceValue : Util::renderScalarValue( $this->fixedSourceValue );
    }

    /**
     * @return bool
     */
    public function isFixedSourceValueSet() {
        return ( null !== $this->fixedSourceValue );
    }

    /**
     * @param bool|float|int|string $fixedSourceValue
     * @return static
     * @throws InvalidArgumentException
     */
    public function setFixedSourceValue( $fixedSourceValue ) {
        if( ! is_scalar( $fixedSourceValue )) {
            throw new InvalidArgumentException( sprintf( self::$ERRx, var_export( $fixedSourceValue, true  )));
        }
        $this->fixedSourceValue = $fixedSourceValue;
        return $this;
    }

    /**
     * @return EntityMgr
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * @return bool
     */
    public function isSourceSet() {
        return ( null !==  $this->source );
    }

    /**
     * Set (EntityMgr) source
     *
     * @param string|EntityMgr $class one of null, self, this, otherClass (fqcn), $class
     * @param mixed            $variable
     * @param int|string       $index
     * @return static
     * @throws InvalidArgumentException
     */
    public function setSource( $class = null, $variable = null, $index = null ) {
        switch( true ) {
            case ( $class instanceof EntityMgr ) :
                $this->source = $class;
                break;
            case (( null === $class ) && ( null === $variable )) :
                $this->source = EntityMgr::init();
                break;
            case ( ! empty( $class ) && is_string( $variable )) :
                $this->source = EntityMgr::factory( $class, $variable, $index );
                break;
            case ( is_scalar( $variable ) && ! Util::isConstant( $variable ) && ! Util::isVarPrefixed( $variable )) :
                // and empty class
                $this->setFixedSourceValue( $variable );
                break;
            default :
                $this->source = EntityMgr::factory( $class, $variable, $index );
                break;
        }
        return $this;
    }

    /**
     * @return ChainInvokeMgr
     */
    public function getFcnInvoke() {
        return $this->fcnInvoke;
    }

    /**
     * @return bool
     */
    public function isFcnInvokeSet() {
        return ( null !== $this->fcnInvoke );
    }

    /**
     * @param FcnInvokeMgr $chainedInvoke
     * @return static
     */
    public function appendChainedInvoke( FcnInvokeMgr $chainedInvoke ) {
        if( empty( $this->fcnInvoke )) {
            $this->fcnInvoke = ChainInvokeMgr::init();
        }
        $this->fcnInvoke->appendChainedInvoke( $chainedInvoke );
        return $this;
    }

    /**
     * @param FcnInvokeMgr|FcnInvokeMgr[] $fcnInvoke
     * @return static
     */
    public function setFcnInvoke( $fcnInvoke ) {
        static $ERR = 'Error invoking %s::%s';
        switch( true ) {
            case is_array( $fcnInvoke ) :
                if( false ===
                    ( $this->fcnInvoke = call_user_func_array( [ ChainInvokeMgr::class, self::FACTORY ], $fcnInvoke ))
                ) {
                    throw new InvalidArgumentException( sprintf( $ERR, ChainInvokeMgr::class,self::FACTORY ));
                }
                break;
            case $fcnInvoke instanceof FcnInvokeMgr :
                $this->appendChainedInvoke( $fcnInvoke );
                break;
            default :
                $type = is_object( $fcnInvoke ) ? get_class( $fcnInvoke ) : gettype( $fcnInvoke );
                throw new InvalidArgumentException( sprintf( self::$ERRx, $type ));
        }
        return $this;
    }

}
