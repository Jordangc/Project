<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template match="/relvar">
  <xsl:element name="div">
    <xsl:attribute name="class">interface</xsl:attribute>
	<xsl:element name="h2"><xsl:value-of select="relvar_name"/></xsl:element>
    <xsl:element name="form">
      <xsl:attribute name="name"><xsl:value-of select="relvar_name"/></xsl:attribute>
      <xsl:attribute name="id"><xsl:value-of select="relvar_name"/></xsl:attribute>
	  <xsl:attribute name="method">POST</xsl:attribute>
	  <xsl:attribute name="action">[[HANDLER]]</xsl:attribute>
<!--form HEAD-->

  <xsl:element name="table">
<!--table HEAD-->

<xsl:for-each select="attribute">

  <xsl:element name="tr">
<!--tr HEAD-->  

  <xsl:element name="th">
<!--th HEAD--> 

<!--label-->
      <xsl:element name="label">
	    <xsl:attribute name="for">
	      <xsl:value-of select="Field"/>
	    </xsl:attribute>
	    <xsl:value-of select="label"/>
	  </xsl:element>
	  
	</xsl:element>
<!--th TAIL--> 

	<xsl:element name="td">
<!--td HEAD--> 

<!--input-->
      <xsl:choose>
          <xsl:when test="Key=''">
		    <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
	          <xsl:attribute name="name"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="id"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="value">
			    <xsl:choose>
			      <xsl:when test="Default=''">[[<xsl:value-of select="Field"/>]]</xsl:when>
			      <xsl:otherwise><xsl:value-of select="Default"/></xsl:otherwise>
			    </xsl:choose>
			  </xsl:attribute>
		  </xsl:element>
		  </xsl:when>
		  <xsl:when test="Key='MUL'">
            <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
	          <xsl:attribute name="name">
	            <xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/>
	          </xsl:attribute>
			  <xsl:attribute name="id"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="value">
			    <xsl:choose>
			      <xsl:when test="Default=''">[[<xsl:value-of select="Field"/>]]</xsl:when>
			      <xsl:otherwise><xsl:value-of select="Default"/></xsl:otherwise>
			    </xsl:choose>
			  </xsl:attribute>
  	        </xsl:element>
		  </xsl:when>
		   <xsl:when test="Key='UNI'">
		    <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
	          <xsl:attribute name="name"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="value">
			    <xsl:choose>
			      <xsl:when test="Default=''">[[<xsl:value-of select="Field"/>]]</xsl:when>
			      <xsl:otherwise><xsl:value-of select="Default"/></xsl:otherwise>
			    </xsl:choose>
			  </xsl:attribute>
			  <xsl:attribute name="id"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
  	        </xsl:element>
		    <xsl:element name="select">
	          <xsl:attribute name="name">
	            <xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/>
	          </xsl:attribute>
			  <xsl:attribute name="id"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:element name="option"></xsl:element>
  	        </xsl:element>
		  </xsl:when>
		  <xsl:when test="Key='PRI'">
		    <xsl:element name="input">
              <xsl:attribute name="type">text</xsl:attribute>
	          <xsl:attribute name="name"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="id"><xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="value">
			    <xsl:choose>
			      <xsl:when test="Default=''">[[<xsl:value-of select="Field"/>]]</xsl:when>
			      <xsl:otherwise><xsl:value-of select="Default"/></xsl:otherwise>
			    </xsl:choose>
			  </xsl:attribute>
  	        </xsl:element>
		  </xsl:when>
	  </xsl:choose>
    </xsl:element>
<!--input:Required Notifier-->
    <xsl:element name="td">
	  <xsl:choose>
          <xsl:when test="Null='NO'"> Required
		    <xsl:element name="input">
              <xsl:attribute name="type">hidden</xsl:attribute>
	          <xsl:attribute name="name">req_<xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="value">1</xsl:attribute>
  	        </xsl:element>
		  </xsl:when>
		  <xsl:when test="Null='YES'"> Optional
		    <xsl:element name="input">
              <xsl:attribute name="type">hidden</xsl:attribute>
	          <xsl:attribute name="name">req_<xsl:value-of select="/relvar/relvar_name"/>_<xsl:value-of select="Field"/></xsl:attribute>
			  <xsl:attribute name="value">0</xsl:attribute>
  	        </xsl:element>
		  </xsl:when>
	  </xsl:choose>
  
    </xsl:element>
	</xsl:element>
	<!--tr TAIL-->
<!--td TAIL--> 
  </xsl:for-each>

<!--interface identifier-->
	  <xsl:element name="input">
        <xsl:attribute name="type">hidden</xsl:attribute>
	    <xsl:attribute name="name">interface</xsl:attribute>
		<xsl:attribute name="value"><xsl:value-of select="relvar_name"/></xsl:attribute>
  	  </xsl:element>
  
  
  <!--submit button-->
	<xsl:element name="tr">
	  <xsl:element name="th"><xsl:element name="label"><xsl:attribute name="for">submit</xsl:attribute>submit</xsl:element></xsl:element>
	  <xsl:element name="td">
	    <xsl:element name="input">
              <xsl:attribute name="type">submit</xsl:attribute>
			  <xsl:attribute name="name">submit</xsl:attribute>
	          <xsl:attribute name="value">submit</xsl:attribute>
  	        </xsl:element>
	  </xsl:element>
	</xsl:element>
 
	</xsl:element>
<!--table TAIL-->

    </xsl:element>
<!--form TAIL--> 
    <xsl:element name="ul">
	  <xsl:attribute name="id">interface_navi</xsl:attribute>
	  <xsl:attribute name="class">clearfix</xsl:attribute>
	  <xsl:element name="li">[[prev_form]]</xsl:element>
	  <xsl:element name="li">[[next_form]]</xsl:element>
	</xsl:element>

    </xsl:element>
<!--div TAIL--> 

  </xsl:template>
</xsl:stylesheet>