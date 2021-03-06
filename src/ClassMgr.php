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
use Kigkonsult\PcGen\Dto\VarDto;
use RuntimeException;

final class ClassMgr extends BaseB
{

    /**
     * @var string  targetTypes
     */
    private static $class     = 'class';
    private static $interface = 'interface';
    private static $trait     = 'trait';

    /*
     * One of class / interface / trait
     *
     * @var string
     */
    private $targetType = null;

    /*
     * Class namespace
     *
     * @var string
     */
    private $namespace = null;

    /**
     * Class use / imports
     *
     * @var array
     */
    private $uses = null;

    /**
     * The class docBlock
     *
     * @var DocBlockMgr
     */
    private $docBlock = null;

    /**
     * True if class is abstract
     *
     * @var bool
     */
    private $abstract = false;

    /**
     * Class extends
     *
     * @var string
     */
    private $extend = null;

    /**
     * Class implements
     *
     * @var array
     */
    private $implements = null;

    /**
     * @var bool
     */
    private $construct = false;

    /**
     * @var bool
     */
    private $factory = false;

    /**
     * @var PropertyMgr[]
     */
    private $properties = [];

    /**
     * ClassMgr constructor
     *
     * @param null $eol
     * @param null $indent
     */
    public function __construct( $eol = null, $indent = null ) {
        parent::__construct( $eol, $indent );
        $this->targetType = self::$class;
    }

    /**
     * Return code as array (with NO eol at line endings)
     *
     * @return array
     * @throws RuntimeException
     */
    public function toArray() {
        static $NAME = 'name';
        if( ! $this->isNameSet()) {
            throw new RuntimeException( sprintf( self::$ERR1, $NAME ));
        }
        $this->checkFixIteratorInterface();
        $code = array_merge(
            $this->initCode(),
            [ self::$CODEBLOCKSTART ],
            $this->bodyCode(),
            [ self::$CODEBLOCKEND ]
        );
        return Util::nullByteClean( $code );
    }

    /**
     * @return void
     */
    private function checkFixIteratorInterface() {
        if( ! $this->hasOneArrayProperty()) {
            return;
        }
        if( $this->isNamespaceSet()) {
            foreach( ClassMethodFactory::$USES as $use ) {
                $this->addUse( $use );
            }
        }
        foreach( ClassMethodFactory::$IMPLEMENTS as $implement ) {
            $this->addImplement( $implement );
        }
        $this->addProperty( ClassMethodFactory::getPositionProperty());
    }

    /**
     * @return array
     */
    private function initCode() {
        $TMPL1 = 'namespace %s;';
        $TMPL2 = 'use %s;';
        $TMPL3 = 'use %s as %s;';
        $TMPL4 = 'abstract ';
        $TMPL5 = ' extends ';
        $TMPL6 = ' implements ';
        if( ! $this->isDocBlockSet()) {
            $this->docBlock = DocBlockMgr::init();
        }
        if( ! $this->docBlock->isSummarySet()) {
            $this->docBlock->setSummary(ucfirst( $this->getTargetType()) . self::$SP1 . $this->getName());
        }
        $this->setBaseIndent( self::$DEFAULTINDENT );
        $code = [];
        if( ! empty( $this->namespace )) {
            $code[] = sprintf( $TMPL1, $this->namespace );
            if( ! $this->docBlock->isTagSet( self::PACKAGE_T )) {
                $this->docBlock->setTag( self::PACKAGE_T, $this->namespace );
            }
        }
        if( ! empty( $this->uses )) {
            $code[] = self::$SP0;
            asort( $this->uses, SORT_FLAG_CASE | SORT_STRING );
            foreach( $this->uses as $alias => $fqcn ) {
                $code[] = ctype_digit((string) $alias ) ? sprintf( $TMPL2, $fqcn ) : sprintf( $TMPL3, $fqcn, $alias );
            }
        }
        $code   = array_merge( $code, $this->docBlock->setBaseIndent()->toArray());
        $row    = $this->isAbstract() ? $TMPL4 : self::$SP0;
        $code[] = $row . $this->getTargetType() . self::$SP1 . $this->getName();
        if( ! empty( $this->extend )) {
            $code[] = $this->indent . $TMPL5 . $this->extend;
        }
        if( ! empty( $this->implements )) {
            $code[] = $this->indent . $TMPL6 . implode( self::$COMMA . self::$SP1, $this->implements );
        }
        return $code;
    }

    /**
     * @return array
     */
    private function bodyCode() {
        $hasProperties = ! empty( $this->properties );
        return array_merge(
            ( $hasProperties     ? $this->defineProperties() : [] ),
            ( $this->construct   ? ClassMethodFactory::renderConstructorMethod( $this->getName()) : [] ),
            ( $this->factory     ? ClassMethodFactory::renderFactoryMethod( $this ) : [] ),
            ( $this->isBodySet() ? array_merge( [ self::$SP0 ], $this->getBody()) : [] ),
            ( $hasProperties     ? $this->produceMethodsForProperties() : [] )
        );
    }

    /**
     * @return array
     */
    private function defineProperties() {
        $code = [];
        foreach( $this->getPropertyIndex() as $pIx ) {
            $propertyMgr = $this->properties[$pIx];
            $dbg = DocBlockMgr::init()
                ->setSummary( $propertyMgr->getVarDto()->getSummary())
                ->setDescription( $propertyMgr->getVarDto()->getDescription());
            $varType = $propertyMgr->getVarDto()->getParamTagVarType();
            if( $propertyMgr->isConst()) {
                $dbg->setDescription( self::CONST_ . self::$SP1 . $varType);
            }
            else {
                $dbg->setTag( self::VAR_T, $varType );
            }
            $code = array_merge( $code,
                $dbg->toArray(),
                $propertyMgr->toArray()
            );
        } // end foreach
        return $code;
    }

    /**
     * @return array
     */
    private function produceMethodsForProperties() {
        $code    = [];
        $oneOnly = $this->hasOneArrayProperty();
        foreach( $this->getPropertyIndex() as $pIx ) {
            $varDto = $this->properties[$pIx]->getVarDto();
            switch( true ) {
                case ( ! $this->properties[$pIx]->isMakeGetter()) :
                    break;
                case $oneOnly :
                    ClassMethodFactory::renderIteratorGetterMethods( $varDto, $code );
                    break;
                default :
                    ClassMethodFactory::renderGetterMethod( $varDto, $code );
                    ClassMethodFactory::renderIsPropertySetMethod( $varDto, $code );
                    ClassMethodFactory::renderPropertyCountMethod( $varDto, $code );
                    break;
            } // end switch
            if( $this->properties[$pIx]->isMakeSetter()) {
                if( $varDto->isTypedArray()) {
                    ClassMethodFactory::renderAppendArrayMethod( $varDto, $code );
                }
                ClassMethodFactory::renderSetterMethod( $varDto, $code );
            }
        } // end foreach
        return $code;
    }


    /**
     * @return string
     */
    private function getTargetType() {
        return $this->targetType;
    }

    /**
     * @return ClassMgr
     */
    public function setClass() {
        $this->targetType = self::$class;
        return $this;
    }
    /**
     * @return ClassMgr
     */
    public function setInterface() {
        $this->targetType = self::$interface;
        return $this;
    }
    /**
     * @return ClassMgr
     */
    public function setTrait() {
        $this->targetType = self::$trait;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNamespaceSet() {
        return ( null !== $this->namespace );
    }

    /**
     * @param string $namespace
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function setNamespace( $namespace ) {
        Assert::assertFqcn( $namespace );
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Add class use, fqcn [, alias ]
     *
     * @param string $fqcn
     * @param string $alias
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function addUse( $fqcn, $alias = null ) {
        if( is_array( $this->uses ) && in_array( $fqcn, $this->uses )) {
            foreach( array_keys( $this->uses, $fqcn ) as $key ) {
                if(( null === $alias ) && is_int( $key )) {
                    return $this; // duplicate (on fqcn and no alias)
                }
                if( $key == $alias ) {
                    return $this; // also duplicate (on fqcn and alias)
                }
            }
        }
        Assert::assertFqcn( $fqcn );
        $key = ( null === $alias ) ? count( $this->uses ) : Assert::assertPhpVar( $alias );
        if( ! is_array( $this->uses )) {
            $this->uses = [];
        }
        $this->uses[$key] = $fqcn;
        return $this;
    }

    /**
     * Set array of class use set , each array element : array( fqcn [, alias ] )
     *
     * @param array $uses
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function setUses( $uses ) {
        static $USE = 'use';
        if( empty( $uses )) {
            throw new InvalidArgumentException( sprintf( self::$ERR1, $USE ));
        }
        $this->uses = [];
        foreach( $uses as $useSet ) {
            if( empty( $useSet )) {
                throw new InvalidArgumentException( sprintf( self::$ERR1, $USE ));
            }
            $this->addUse(
                $useSet[0],
                ( isset( $useSet[1] ) ? $useSet[1] : null )
            );
        } // end foreach
        return $this;
    }

    /**
     * @return DocBlockMgr
     */
    public function getDocBlock() {
        return $this->docBlock;
    }

    /**
     * @return bool
     */
    public function isDocBlockSet() {
        return ( null !== $this->docBlock );
    }

    /**
     * @param DocBlockMgr $docBlock
     * @return ClassMgr
     */
    public function setDocBlock( $docBlock ) {
        $this->docBlock = $docBlock;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAbstract() {
        return $this->abstract;
    }

    /**
     * Set class as abstract
     *
     * @param bool $abstract
     * @return ClassMgr
     */
    public function setAbstract( $abstract ) {
        $this->abstract = (bool) $abstract;
        return $this;
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function getExtend() {
        return $this->extend;
    }

    /**
     * @return bool
     */
    public function isExtendsSet() {
        return ( null !== $this->extend );
    }

    /**
     * @param string $extend
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function setExtend( $extend ) {
        Assert::assertFqcn( $extend );
        $this->extend = $extend;
        return $this;
    }

    /**
     * @param string $implement
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function addImplement( $implement ) {
        if( is_array( $this->implements ) && in_array( $implement, $this->implements )) {
            return $this;
        }
        Assert::assertFqcn( $implement );
        if( ! is_array( $this->implements )) {
            $this->implements = [];
        }
        $this->implements[] = $implement;
        return $this;
    }

    /**
     * Set class interface implements
     *
     * @param array $implements
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function setImplements( array $implements ) {
        static $IMPLEMENTS = 'implements';
        if( empty( $implements )) {
            throw new InvalidArgumentException( sprintf( self::$ERR1, $IMPLEMENTS ));
        }
        $this->implements = [];
        foreach( $implements as $implement ) {
            if( empty( $implement )) {
                throw new InvalidArgumentException( sprintf( self::$ERR1, $IMPLEMENTS ));
            }
            $this->addImplement( $implement );
        }
        return $this;
    }

    /**
     * @param bool $construct
     * @return ClassMgr
     */
    public function setConstruct( $construct = true ) {
        $this->construct = (bool) $construct;
        return $this;
    }

    /**
     * @param bool $factory
     * @return ClassMgr
     */
    public function setFactory( $factory = true ) {
        $this->factory = (bool) $factory;
        return $this;
    }

    /**
     * Add property
     *
     *     PropertyMgr
     *     VariableMgr, getter, setter, argInFactory
     *     VarDto, getter, setter, argInFactory
     *     ( array, to conform to setProperty)
     *     variable, varType, default, summary, description, getter, setter, argInFactory
     *
     * @param string|PropertyMgr|VariableMgr|VarDto $name
     * @param string  $type
     * @param mixed   $default
     * @param string  $summary
     * @param string  $description
     * @param bool    $getter
     * @param bool    $setter
     * @param bool    $argInFactory
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function addProperty(
        $name,
        $type = null,
        $default = null,
        $summary = null,
        $description = null,
        $getter = true,
        $setter = true,
        $argInFactory = true
    ) {
        if( is_array( $name )) {
            $name = array_values( $name );
            list( $name, $type, $default, $summary, $description, $getter, $setter, $argInFactory ) =
                array_pad( $name, 8, null );
        }
        switch( true ) {
            case ( $name instanceof PropertyMgr ) :
                $property = $name;
                break;
            case ( $name instanceof VariableMgr ) :
                $name = $name->getVarDto();
            // fall through
            case ( $name instanceof VarDto ) :
                $property = PropertyMgr::factory( $name )
                    ->setMakeGetter(( null === $type )        ? true  : (bool) $type )     // getter
                    ->setMakeSetter(( null === $default )     ? true  : (bool) $default )  // setter
                    ->setArgInFactory( ( null === $summary ) ? false : (bool) $summary ); // arg in factory
                break;
            case is_string( $name ) :
                $property = PropertyMgr::factory( VarDto::factory( $name, $type, $default, $summary, $description ))
                    ->setMakeGetter( ( null === $getter )          ? true  : (bool) $getter )        // getter
                    ->setMakeSetter( ( null === $setter )          ? true  : (bool) $setter )        // setter
                    ->setArgInFactory( ( null === $argInFactory ) ? false : (bool) $argInFactory ); // arg in factory
                break;
            default :
                throw new InvalidArgumentException( sprintf( self::$ERRx, var_export( $name, true )));
                break;

        } // end switch
        $this->properties[] = $property;
        return $this;
    }

    /**
     * @param $pIx
     * @return PropertyMgr
     */
    public function getProperty( $pIx ) {
        return $this->properties[$pIx];
    }

    /**
     * @return int
     */
    public function getPropertyCount() {
        return count( $this->properties );
    }

    /**
     * @return array
     */
    public function getPropertyIndex() {
        return array_keys( $this->properties );
    }

    /**
     * @return bool
     */
    private function hasOneArrayProperty() {
        $cntProps = $this->getPropertyCount();
        switch( true ) {
            case ( empty( $cntProps ) || ( 2 < $cntProps )) :
                break;
            case ( true !== ( $this->properties[0]->getVarDto()->isTypedArray() &&
                    ! $this->properties[0]->isConst() &&
                    ! $this->properties[0]->isStatic())) :
                break;
            case ( 1 == count( $this->properties )) :
                return true;
                break;
            case ( ClassMethodFactory::$POSITION == $this->properties[1]->getVarDto()->getName()) :
                return true;
                break;
            default :
                break;
        }
        return false;
    }

    /**
     * Set array of properties
     *
     * Input array item :
     *     PropertyMgr
     *     VariableMgr
     *     VarDto
     *     variable
     *     array( VariableMgr, getter, setter, argInFactory )
     *     array( VarDto, getter, setter, argInFactory )
     *     array( variable, varType, default, summary, description, getter, setter, argInFactory )
     *
     * @param mixed $properties
     * @return ClassMgr
     * @throws InvalidArgumentException
     */
    public function setProperties( $properties ) {
        static $ERRx1 = 'Invalid property (#%d) %s';
        $this->properties = [];
        if( ! is_array( $properties )) {
            $properties = [ $properties ];
        }
        foreach( $properties as $pIx => $property ) {
            switch( true ) {
                case empty( $property ) :
                    throw new InvalidArgumentException( sprintf( $ERRx1, $pIx, var_export( $property, true )));
                    break;
                case ( $property instanceof PropertyMgr ) :
                    $this->addProperty( $property );
                    break;
                case ( $property instanceof VariableMgr ) :
                    $property = $property->getVarDto();
                    // fall through
                case ( $property instanceof VarDto ) :
                    // fall through
                case ( is_string( $property )) :
                    $this->addProperty(
                        PropertyMgr::factory( $property )
                            ->setMakeGetter( true)
                            ->setMakeSetter( true)
                            ->setArgInFactory( false )
                    );
                    break;
                case ( ! is_array( $property )) :
                    throw new InvalidArgumentException( sprintf( $ERRx1, $pIx, var_export( $property, true )));
                    break;
                case ( $property[0] instanceof VarDto ) :
                    $this->addProperty(
                        PropertyMgr::factory( $property[0] )
                            ->setMakeGetter( Util::getIfSet( $property, 1, self::BOOL_T, true ) )  // make getter,
                            ->setMakeSetter( Util::getIfSet( $property, 2, self::BOOL_T, true ) )  // make getter
                            ->setArgInFactory( Util::getIfSet( $property, 1, self::BOOL_T, false ) ) // argInFactory
                    );
                    break;
                default :
                    $this->addProperty(
                        PropertyMgr::factory(
                            VarDto::factory(
                                Util::getIfSet( $property, 0 ),                           // variable,
                                Util::getIfSet( $property, 1 ),                           // varType,
                                Util::getIfSet( $property, 2 ),                           // default
                                Util::getIfSet( $property, 3, self::STRING_T ),           // summary
                                Util::getIfSet( $property, 4, self::ARRAY_T )             // description
                            )
                        )
                            ->setMakeGetter(
                                Util::getIfSet( $property, 5, self::BOOL_T, true )  // make getter
                            )
                            ->setMakeGetter(
                                Util::getIfSet( $property, 6, self::BOOL_T, true )  // make getter
                            )
                            ->setArgInFactory(
                                Util::getIfSet( $property, 7, self::BOOL_T, false ) // argInFactory
                            )
                    );
                    break;
            } // end switch
        } // end foreach
        return $this;
    }

}
