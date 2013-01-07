<?xml version="1.0" encoding="UTF-8"?>
<!--
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    *                                                                          *
    * This file is part of ALIX.                                               *
    *                                                                          *
    * ALIX is free software: you can redistribute it and/or modify             *
    * it under the terms of the GNU General Public License as published by     *
    * the Free Software Foundation, either version 3 of the License, or        *
    * (at your option) any later version.                                      *
    *                                                                          *
    * ALIX is distributed in the hope that it will be useful,                  *
    * but WITHOUT ANY WARRANTY; without even the implied warranty of           *
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            *
    * GNU General Public License for more details.                             *
    *                                                                          *
    * You should have received a copy of the GNU General Public License        *
    * along with ALIX.  If not, see <http://www.gnu.org/licenses/>.            *
    \**************************************************************************/
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <xsl:template name="Item">
  	<xsl:param name="ItemValue" />
  	<xsl:param name="FlagValue" />
  	<xsl:param name="SDVcheck"/>
  	<xsl:param name="ItemLocked"/>
    <xsl:param name="ItemDecode"/>                   
	  <xsl:param name="MaxAuditRecordID"/>
  	<xsl:param name="TabIndex"/>
  	<xsl:param name="CurrentItemGroupOID"/>
    <xsl:param name="CurrentItemGroupRepeatKey"/>
    <xsl:param name="Item"/>
    <xsl:param name="ForceSelect"/> <!--Force l'utilisation d'un select, même si on a moins de 4 réponses-->
    <xsl:param name="CurrentApp"/>
    <xsl:param name="ProfileId"/>
  
    <!--On doit modifier les OID, car à la soumission d'un formulaire les navigateurs remplacent les "." par des "_" -->
  	<xsl:variable name="ItemOID" select="translate($Item/@OID,'.','@')"/>

    <xsl:choose>
      <xsl:when test="$ItemLocked='Y' or ($SDVcheck='Y' and $ProfileId!='CRA')">
        <!--span for lock icon-->
        <xsl:element name="span">
          <xsl:attribute name='class'>imageOnly image16</xsl:attribute>
          <xsl:if test="$ItemLocked='Y'">
            <xsl:attribute name="style">background-image: url('<xsl:value-of select="$CurrentApp"/>/templates/default/images/lock_16.png');</xsl:attribute>
          </xsl:if>
        </xsl:element>
        <!--span for SDV icon-->
        <xsl:element name="span">
          <xsl:attribute name='class'>imageOnly image16</xsl:attribute>
          <xsl:if test="$SDVcheck='Y' and $ProfileId!='CRA'">
            <xsl:attribute name="style">background-image: url('<xsl:value-of select="$CurrentApp"/>/templates/default/images/agt_action_success.png');</xsl:attribute>
          </xsl:if>
        </xsl:element>
        <!--item value as text only-->
        <xsl:value-of select="$ItemDecode" />
      </xsl:when>
      <!--xsl:when test="$SDVcheck='Y' and $ProfileId!='CRA'">
        <xsl:element name="span">
          <xsl:attribute name='class'>imageOnly image16</xsl:attribute>
          <xsl:attribute name="style">background-image: url('<xsl:value-of select="$CurrentApp"/>/templates/default/images/agt_action_success.png');</xsl:attribute>
        </xsl:element>
        <xsl:value-of select="$ItemDecode" />
      </xsl:when-->
      <xsl:otherwise>
        <xsl:choose>
          <!--Derived item : no input, just display the raw value-->
          <xsl:when test="$Item/@MethodOID!=''">
            <xsl:if test="$ItemValue=''">_<!--show an empy field with an underscore when value is empty or missing, an icon would be better ? only when value is missing ?-->
            </xsl:if>
            <span name='method'><xsl:value-of select="$ItemValue"/></span>
        	  <xsl:element name="input">
              <xsl:attribute name="type">hidden</xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="$ItemValue"/></xsl:attribute>
        			<xsl:attribute name="oldvalue"><xsl:value-of select="$ItemValue"/></xsl:attribute>
        			<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
        			<xsl:attribute name="MaxAuditRecordID"><xsl:value-of select="$MaxAuditRecordID"/></xsl:attribute>
        			<xsl:attribute name="itemoid"><xsl:value-of select="$Item/@OID"/></xsl:attribute>
              <xsl:attribute name="name">text_<xsl:value-of select="@DataType"/>_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
            </xsl:element>
          </xsl:when>      
          <!--Item associé à une codelist-->
          <xsl:when test="count($Item/CodeList/CodeListItem)!=0">
            <!--Si nous sommes en présence d'une codelist à 2,3 ou 4 réponses, et que les libellés sont courts, on présente des radios button-->
            <xsl:choose>
       				<xsl:when test="$ForceSelect='' and($ItemOID='TRT@ACTION' or count($Item/CodeList/CodeListItem)&lt;=4 and string-length($Item/CodeList/CodeListItem[position()=1]/@Decode)&lt;5)">
      				    <xsl:for-each select="$Item/CodeList/CodeListItem">
                    <xsl:variable name="InputId">radio_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/>_<xsl:value-of select="position()"/></xsl:variable>
                    <xsl:element name="input">
      				    	 <xsl:attribute name="class">inputItem</xsl:attribute>
                     <xsl:attribute name="type">radio</xsl:attribute>
      				    	 <xsl:attribute name="name">radio_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
      				    	 <xsl:attribute name="id"><xsl:value-of select="$InputId"/></xsl:attribute>
      				    	 <xsl:attribute name="itemoid"><xsl:value-of select="$Item/@OID"/></xsl:attribute>
      							 <xsl:attribute name="value"><xsl:value-of select="@CodedValue"/></xsl:attribute>
      							 <xsl:attribute name="oldvalue"><xsl:value-of select="$ItemValue"/></xsl:attribute>
      							 <xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
      							 <xsl:attribute name="MaxAuditRecordID"><xsl:value-of select="$MaxAuditRecordID"/></xsl:attribute>                 
                     <xsl:if test="@CodedValue=$ItemValue">
      				    	   <xsl:attribute name="checked">checked</xsl:attribute>
                     </xsl:if>
      				    	</xsl:element>
                    <!--If not Codelist decode associated, raw value is displayed-->
                    <label for="{$InputId}">
                      <xsl:if test="@Decode=''"><xsl:value-of select="@CodedValue"/></xsl:if>
      				        <xsl:value-of select="@Decode"/>
                    </label>  				    	
      				    </xsl:for-each>
      				</xsl:when>
      		    <xsl:otherwise> 
      				  <xsl:element name="select">
        				<xsl:attribute name="class">inputItem</xsl:attribute>
                <xsl:attribute name="oldvalue"><xsl:value-of select="$ItemValue"/></xsl:attribute>
        				<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
                <xsl:attribute name="MaxAuditRecordID"><xsl:value-of select="$MaxAuditRecordID"/></xsl:attribute>
        				<xsl:attribute name="itemoid"><xsl:value-of select="$Item/@OID"/></xsl:attribute>
                <xsl:attribute name="name">select_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
      				  	<option value="">...</option>
      				    <xsl:for-each select="$Item/CodeList/CodeListItem">
      				      <xsl:element name="option">
      				        <xsl:attribute name="value"><xsl:value-of select="@CodedValue"/></xsl:attribute>
      				        <xsl:if test="@CodedValue=$ItemValue">
      				        	<xsl:attribute name="selected">selected</xsl:attribute>
      				        </xsl:if>
      				        <xsl:if test="@Decode=''"><xsl:value-of select="@CodedValue"/></xsl:if>
      				        <xsl:value-of select="@Decode"/>
      				      </xsl:element>
      				    </xsl:for-each>
      				  </xsl:element>
      				</xsl:otherwise>
      		  </xsl:choose>  
          </xsl:when>
          <!--Item de type date-->
          <xsl:when test="$Item/@DataType='date' or $Item/@DataType='partialDate'">
            <xsl:element name="span">
              <xsl:attribute name="name">day</xsl:attribute>
              <xsl:if test="$Item/@DataType='partialDate'">
                <xsl:attribute name="class">optionalText inputItem</xsl:attribute>
              </xsl:if> 
              <xsl:choose>
                <xsl:when test="$lang='el'">Ημέρα:</xsl:when>
                <xsl:when test="$lang='fr'">jour:</xsl:when>
                <xsl:when test="$lang='de'">Tag:</xsl:when>
                <xsl:otherwise>day:</xsl:otherwise>
              </xsl:choose>
            </xsl:element>                      	
            <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText inputItem</xsl:attribute>
      			  <xsl:attribute name="itemoid">
      				  <xsl:value-of select="$Item/@OID"/>
      			  </xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring($ItemValue,9,2)"/></xsl:attribute>
      				<xsl:attribute name="oldvalue">
      					 <xsl:value-of select="substring($ItemValue,9,2)"/>
      				</xsl:attribute>
      				<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
      				<xsl:attribute name="MaxAuditRecordID">
      					<xsl:value-of select="$MaxAuditRecordID"/>
      				</xsl:attribute>
              <xsl:attribute name="name">text_dd_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength">2</xsl:attribute>
              <xsl:attribute name="size">2</xsl:attribute>
            </xsl:element> 
            <xsl:element name="span">
              <xsl:attribute name="name">month</xsl:attribute>
              <xsl:if test="$Item/@DataType='partialDate'">
                <xsl:attribute name="class">optionalText</xsl:attribute>
              </xsl:if> 
              <xsl:choose>
                <xsl:when test="$lang='el'">Μήνας:</xsl:when>
                <xsl:when test="$lang='fr'">mois:</xsl:when>
                <xsl:when test="$lang='de'">Monat:</xsl:when>
                <xsl:otherwise>month:</xsl:otherwise>
              </xsl:choose>
            </xsl:element>
            <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText inputItem</xsl:attribute>
      			  <xsl:attribute name="itemoid">
      				  <xsl:value-of select="$Item/@OID"/>
      			  </xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring($ItemValue,6,2)"/></xsl:attribute>
      				<xsl:attribute name="oldvalue">
      					 <xsl:value-of select="substring($ItemValue,6,2)"/>
      				</xsl:attribute>
      				<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
              <xsl:attribute name="MaxAuditRecordID">
      					<xsl:value-of select="$MaxAuditRecordID"/>
      				</xsl:attribute>
              <xsl:attribute name="name">text_mm_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength">2</xsl:attribute>
              <xsl:attribute name="size">2</xsl:attribute>
            </xsl:element>
            <xsl:element name="span">
              <xsl:attribute name="name">year</xsl:attribute>
              <xsl:choose>
                <xsl:when test="$lang='el'">Έτος:</xsl:when>
                <xsl:when test="$lang='fr'">Année:</xsl:when>
                <xsl:when test="$lang='de'">Jahr:</xsl:when>
                <xsl:otherwise>year:</xsl:otherwise>
              </xsl:choose>
            </xsl:element>
            <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText inputItem</xsl:attribute>
      			  <xsl:attribute name="itemoid">
      				<xsl:value-of select="$Item/@OID"/>
      			  </xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring($ItemValue,1,4)"/></xsl:attribute>
      				<xsl:attribute name="oldvalue">
      					 <xsl:value-of select="substring($ItemValue,1,4)"/>
      				</xsl:attribute>
      				<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
              <xsl:attribute name="MaxAuditRecordID">
      					<xsl:value-of select="$MaxAuditRecordID"/>
      				</xsl:attribute>
              <xsl:attribute name="name">text_yy_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength">4</xsl:attribute>
              <xsl:attribute name="size">4</xsl:attribute>
            </xsl:element>           
          </xsl:when>
          <!--Item de type float : on décompose partie entière/partie décimale -->
          <xsl:when test="$Item/@DataType='float'">
            <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText inputItem</xsl:attribute>
      				<xsl:attribute name="itemoid">
      					<xsl:value-of select="$Item/@OID"/>
      				</xsl:attribute>
      				<xsl:attribute name="oldvalue">
      					<xsl:value-of select="substring-before($ItemValue,'.')"/>
      				</xsl:attribute>
      				<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
              <xsl:attribute name="MaxAuditRecordID">
      					<xsl:value-of select="$MaxAuditRecordID"/>
      				</xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring-before($ItemValue,'.')"/></xsl:attribute>
              <xsl:attribute name="name">text_int_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength"><xsl:value-of select="@Length - @SignificantDigits - 1"/></xsl:attribute>
              <xsl:attribute name="size"><xsl:value-of select="@Length - @SignificantDigits - 1"/></xsl:attribute>
            </xsl:element>      	
            <strong>.</strong>
            <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText inputItem</xsl:attribute>
      				<xsl:attribute name="itemoid">
      					<xsl:value-of select="$Item/@OID"/>
      				</xsl:attribute>
      				<xsl:attribute name="oldvalue">
      					<xsl:value-of select="substring-before($ItemValue,'.')"/>
      				</xsl:attribute>
      				<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
              <xsl:attribute name="MaxAuditRecordID">
      					<xsl:value-of select="$MaxAuditRecordID"/>
      				</xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring-after($ItemValue,'.')"/></xsl:attribute>
              <xsl:attribute name="name">text_dec_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength"><xsl:value-of select="@SignificantDigits"/></xsl:attribute>
              <xsl:attribute name="size"><xsl:value-of select="@SignificantDigits"/></xsl:attribute>
            </xsl:element>      
          </xsl:when>
          <xsl:when test="$Item/@DataType='text'">   
            <textarea>
              <xsl:attribute name="cols">55</xsl:attribute>
              <xsl:attribute name="rows">3</xsl:attribute>
              <xsl:attribute name="maxlength"><xsl:value-of select="@Length"/></xsl:attribute>          
              <xsl:attribute name="class">inputText inputItem</xsl:attribute>
      				<xsl:attribute name="itemoid">
      					<xsl:value-of select="$Item/@OID"/>
      				</xsl:attribute>
      				<xsl:attribute name="oldvalue"><xsl:value-of select="$ItemValue"/></xsl:attribute>
      				<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
              <xsl:attribute name="MaxAuditRecordID"><xsl:value-of select="$MaxAuditRecordID"/></xsl:attribute>
              <xsl:attribute name="name">text_text_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:if test="string-length($ItemValue)=0">EMPTY</xsl:if>
              <xsl:value-of select="$ItemValue"/>
            </textarea>
          </xsl:when>
          <xsl:otherwise>
        	  <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText inputItem</xsl:attribute>
              <xsl:if test="$ItemValue!=''">
                <xsl:attribute name="value"><xsl:value-of select="$ItemValue"/></xsl:attribute>
              </xsl:if>  
        			<xsl:attribute name="oldvalue"><xsl:value-of select="$ItemValue"/></xsl:attribute>
        			<xsl:attribute name="flagvalue"><xsl:value-of select="$FlagValue"/></xsl:attribute>
        			<xsl:attribute name="MaxAuditRecordID"><xsl:value-of select="$MaxAuditRecordID"/></xsl:attribute>
        			<xsl:attribute name="itemoid"><xsl:value-of select="$Item/@OID"/></xsl:attribute>
              <xsl:attribute name="name">text_<xsl:value-of select="@DataType"/>_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="size"><xsl:value-of select="@Length"/></xsl:attribute>
              <xsl:attribute name="maxlength"><xsl:value-of select="@Length"/></xsl:attribute>
            </xsl:element>        
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
    <!--Print Unit if defined-->
    <xsl:if test="MeasurementUnit/MeasurementUnitItem/@Symbol">
      &#160;<xsl:value-of select="MeasurementUnit/MeasurementUnitItem/@Symbol"/>
    </xsl:if>  
  </xsl:template>

</xsl:stylesheet>