[comment]: # (This file is part of PcGen, PHP Code Generation support package. Copyright 2020 Kjell-Inge Gustafsson, kigkonsult, All rights reserved, licence GPL 3.0)

#### AssignClauseMgr

The ```AssignClauseMgr``` manages the code assign 
* of a target class property or variable (value) 
* from 
  * source class property or variable (value), opt (int/variable) index
  * (scalar) fixedSourceValue
  * single function/method or chained invokes
  * (but not 'body' code)
* the target and source (inner class) [EntityMgr] sets has
  * ```class``` - one of null, self, $this, 'otherClass', '$class'
  * ```variable``` - variable/property name
  * ```index``` - opt array index
* default assign operator is ```=```
* ex result ```$this->property = OtherClass::CONSTANT;```

The result of this class toString()/toArray() methods is used by other classes setBody() method.

###### AssignClauseMgr Methods

---
Inherited [Common methods]

---

```AssignClauseMgr::factory( [ targetClass [, targetVariable, [, targetTndex [, sourceClass [, sourceVariable, [, sourceTndex ]]]]]] )```
* ```targetClass``` _string_ one of null, self, $this, 'otherClass', '$class'
  * convenient constants found in PcGenInterface 
* ```targetVariable``` _string_ variable/property name
  * uppercase is autodetected as CONSTANT
  * variable $-prefixed
* ```targetIndex```  _int_|_string_ opt array index
* ```sourceClass``` _string_ one of null, self,  $this, 'otherClass', '$class'
  * convenient constants found in PcGenInterface 
* ```sourceVariable``` _string_ class/variable/property name
  * uppercase is autodetected as CONSTANT
  * variable $-prefixed
* ```sourceIndex```  _int_|_string_ opt array index
* Return static
---

```AssignClauseMgr::toArray()```
* Return _array_, result code rows (null-bytes removed) no trailing eol
* Throws RuntimeException

```AssignClauseMgr::toString()```
* Return _string_ with code rows (extends toArray), each code row with trailing eol
* Throws RuntimeException
---

```AssignClauseMgr::getTarget()```
* Return [EntityMgr]

```AssignClauseMgr::isTargetSet()```
* Return _bool_ true if not null

```AssignClauseMgr::setTarget( class [, variable, [, index ]] )```
* ```class``` _string_|[EntityMgr] if string, one of null, self, $this, 'otherClass', '$class'
  * convenient constants found in PcGenInterface 
* ```variable``` _string_ class/variable/property name
  * uppercase is autodetected as CONSTANT
  * variable $-prefixed
* ```index```  _int_|_string_ opt array index
* Return static
* Throws InvalidArgumentException

```AssignClauseMgr::setThisPropertyTarget( property [, index ] )```
* convenient shortcut for ```AssignClauseMgr::setTarget()```
* Give target result ```$this->property```
* ```property``` _string_
* ```index```  _int_|_string_ opt array index
* Return static
* Throws InvalidArgumentException

```AssignClauseMgr::setVariableTarget( variable [, index ] )```
* convenient shortcut for ```AssignClauseMgr::setTarget()```
* Give target result ```$variable```
* ```variable``` _string_
* ```index```  _int_|_string_ opt array index
* Return static
* Throws InvalidArgumentException

```AssignClauseMgr::setForceTargetAsInstance( forceTargetAsInstance )```
* Only applicable for '$targetClass', ignored by the others
* ```forceTargetAsInstance``` _bool_
  * true : force ```$targetClass->property```
  * false : NOT, default (```$targetClass::$property```)
* Return _static_
---

```AssignClauseMgr::getFixedSourceValue()```
* Return _bool_|_int_|_float_|_string_, scalar

```AssignClauseMgr::isFixedSourceValueSet()```
* Return _bool_ true if not null

```AssignClauseMgr::setFixedSourceValue( fixedSourceValue )```
* Set a fixed (scalar) source
* ```fixedSourceValue``` _bool_|_int_|_float_|_string_, scalar
* Return _static_
* Throws InvalidException
---

```AssignClauseMgr::getSource()```
* Return _EntityMgr_

```AssignClauseMgr::isSourceSet()```
* Return _bool_ true if not null

```AssignClauseMgr::setSource( class [, variable [, index ]] )```
* ```class``` _string_|[EntityMgr] if string, one of null, self, $this, 'otherClass', '$class'
  * convenient constants found in PcGenInterface 
* ```variable``` _string_ class/variable/property/constant name
  * uppercase is autodetected as CONSTANT
  * variable $-prefixed
* ```index```  _int_|_string_ opt array index
* Return static
* Throws InvalidArgumentException

```AssignClauseMgr::setThisPropertySource( property [, index ] )```
* convenient shortcut for ```AssignClauseMgr::setSource()```
* Give source result ```$this->property```
* ```property``` _string_
* ```index```  _int_|_string_ opt array index
* Return static
* Throws InvalidArgumentException

```AssignClauseMgr::setVariableSource( variable [, index ] )```
* convenient shortcut for ```AssignClauseMgr::setSource()```
* Give source result ```$variable```
* ```variable``` _string_
* ```index```  _int_|_string_ opt array index
* Return static
* Throws InvalidArgumentException
---

```AssignClauseMgr::getFcnInvoke()```
* Return [ChainInvokeMgr] (manages single or chained [FcnInvokeMgr]s)

```AssignClauseMgr::isFcnInvokeSet()```
* Return _bool_ true if not null

```AssignClauseMgr::setFcnInvoke( fcnInvoke )```
* ```fcnInvoke``` [FcnInvokeMgr] | [FcnInvokeMgr]\[]  
* Return static
* Throws InvalidArgumentException
---

```AssignClauseMgr::setOperator( operator )```
* Default assign operator is ```=```  
* ```operator``` _string_, one of ```=```, ```+=```, ..., see [operators]
* Return _static_
* Throws InvalidException
---

<small>Return to [README] - [Summary]</small>

[ChainInvokeMgr]:ChainInvokeMgr.md
[Common methods]:CommonMethods.md
[EntityMgr]:EntityMgr.md
[FcnInvokeMgr]:FcnInvokeMgr.md
[operators]:https://www.php.net/manual/en/language.operators.assignment.php
[PropertyMgr]:PropertyMgr.md
[README]:../README.md
[Summary]:Summary.md
[VariableMgr]:VariableMgr.md
