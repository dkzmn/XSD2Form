<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xs="http://www.w3.org/2001/XMLSchema">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:strip-space elements="*"/>

    <xsl:template match="*">
        <xsl:variable name="elementscount" select="count(*[name()='xs:element'])"/>
        <xsl:copy>
            <xsl:copy-of select="@*"/>
            <xsl:for-each select="node()[name()='xs:import']">
                <xsl:copy-of select="."/>
            </xsl:for-each>
            <xsl:if test="$elementscount &gt; 1">
                <xs:element>
                    <xs:complexType>
                        <xs:sequence>
                            <xsl:for-each select="node()[name()='xs:element']">
                                <xsl:copy-of select="."/>
                            </xsl:for-each>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
                <xsl:copy-of select="node()[name()!='xs:element' and name()!='xs:import']"/>
            </xsl:if>
            <xsl:if test="$elementscount &lt; 2">
                <xsl:copy-of select="node()[name()!='xs:import']"/>
            </xsl:if>
        </xsl:copy>
    </xsl:template>

</xsl:stylesheet>
