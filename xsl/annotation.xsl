<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" standalone="yes"/>

    <xsl:template match="node()|@*">
        <xsl:copy>
            <xsl:apply-templates select="node()|@*" />
        </xsl:copy>
    </xsl:template>

    <xsl:template match="*[annotation/documentation]">
        <xsl:element name="xs:{local-name()}" namespace="http://www.w3.org/2001/XMLSchema">
            <xsl:attribute name="name"  >
                <xsl:value-of select="./annotation/documentation/text()" />
            </xsl:attribute>
            <xsl:copy-of select="@*[name()!='name']|node()[name()!='annotation']" />
        </xsl:element>

    </xsl:template>

</xsl:stylesheet>
