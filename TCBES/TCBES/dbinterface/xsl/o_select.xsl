<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template match="/">
  <xsl:for-each select="select">
    <xsl:element name="select">
	
      <xsl:attribute name="name"><xsl:value-of select="element_name"/></xsl:attribute>
      <xsl:attribute name="id"><xsl:value-of select="element_name"/></xsl:attribute>
	  
	  <xsl:for-each select="option">
	    <xsl:element name="option">
		  <xsl:attribute name="class">if_selection</xsl:attribute>
	      <xsl:attribute name="value"><xsl:value-of select="."/></xsl:attribute>
		  <xsl:value-of select="."/>
	    </xsl:element>
	  </xsl:for-each>
	  
	  <xsl:for-each select="option_new">
	    <xsl:element name="option">
		  <xsl:attribute name="class">newentry</xsl:attribute>
	      <xsl:attribute name="value"><xsl:value-of select="."/></xsl:attribute>
		  <xsl:value-of select="."/>
	    </xsl:element>
	  </xsl:for-each>
	  
    </xsl:element>
	</xsl:for-each>
  </xsl:template>
</xsl:stylesheet>
