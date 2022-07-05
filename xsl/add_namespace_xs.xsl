<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" standalone="yes"/>

    <xsl:template match="node()|@*">
        <xsl:copy>
            <xsl:apply-templates select="node()|@*" />
        </xsl:copy>
    </xsl:template>

    <xsl:template match="*">
        <xsl:element name="xs:{local-name()}" namespace="http://www.w3.org/2001/XMLSchema">
            <xsl:copy-of select="namespace::*"/>
            <xsl:apply-templates select="node()|@*"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="@base[not(contains(., ':'))]">
        <xsl:attribute name="base">
            <xsl:value-of select="concat('xs:',.)"/>
        </xsl:attribute>
    </xsl:template>

    <xsl:template match="@type[not(contains(., ':'))]">
        <xsl:attribute name="type">
            <xsl:value-of select="concat('xs:',.)"/>
        </xsl:attribute>
    </xsl:template>

</xsl:stylesheet>
