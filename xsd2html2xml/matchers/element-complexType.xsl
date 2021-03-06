<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
	version="1.1"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xs="http://www.w3.org/2001/XMLSchema">
	
	<!-- handle complex elements, which optionally contain simple content; forwards them with their namespace documents -->
	<xsl:template match="xs:element[xs:complexType/*[not(self::xs:simpleContent)]]">
		<xsl:param name="root-document" /> <!-- contains root document -->
		<xsl:param name="root-path" /> <!-- contains path from root to included and imported documents -->
		<xsl:param name="root-namespaces" /> <!-- contains root document's namespaces and prefixes -->
		
		<xsl:param name="namespace-prefix" /> <!-- contains inherited namespace prefix -->
		
		<xsl:param name="id" select="@name" /> <!-- contains node name, or references node name in case of groups -->
		<xsl:param name="min-occurs">
			<xsl:choose>
				<xsl:when test="boolean(@minOccurs)">
					<xsl:value-of select="@minOccurs"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="1"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>

		<xsl:param name="max-occurs">
			<xsl:choose>
				<xsl:when test="boolean(@maxOccurs)">
					<xsl:value-of select="@maxOccurs"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="1"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>
		<xsl:param name="simple" /> <!-- indicates if an element allows simple content -->
		<xsl:param name="choice" /> <!-- handles xs:choice elements and descendants; contains a unique ID for radio buttons of the same group to share -->
		<xsl:param name="disabled">false</xsl:param> <!-- is used to disable elements that are copies for additional occurrences -->
		<xsl:param name="xpath" /> <!-- contains an XPath query relative to the current node, to be used with xml document -->
		
		<xsl:call-template name="log">
			<xsl:with-param name="reference">xs:element[xs:complexType/*[not(self::xs:simpleContent)]]</xsl:with-param>
		</xsl:call-template>
		
		<!-- forward -->
		<xsl:call-template name="forward">
			<xsl:with-param name="stylesheet" select="$element-complexType-stylesheet" />
			<xsl:with-param name="template">element-complexType-forwardee</xsl:with-param>
			
			<xsl:with-param name="root-document" select="$root-document" />
			<xsl:with-param name="root-path" select="$root-path" />
			<xsl:with-param name="root-namespaces" select="$root-namespaces" />
			
			<xsl:with-param name="namespace-documents">
				<!-- retrieve element's namespace documents -->
				<xsl:call-template name="get-my-namespace-documents">
					<xsl:with-param name="root-document" select="$root-document" />
					<xsl:with-param name="root-path" select="$root-path" />
					<xsl:with-param name="root-namespaces" select="$root-namespaces" />
				</xsl:call-template>
			</xsl:with-param>
			<xsl:with-param name="namespace-prefix" select="$namespace-prefix" />
			
			<xsl:with-param name="id" select="$id" />
			<xsl:with-param name="min-occurs" select="$min-occurs" />
			<xsl:with-param name="max-occurs" select="$max-occurs" />
			<xsl:with-param name="simple" select="$simple" />
			<xsl:with-param name="choice" select="$choice" />
			<xsl:with-param name="disabled" select="$disabled" />
			<xsl:with-param name="xpath" select="$xpath" />
		</xsl:call-template>
	</xsl:template>
	
	<!-- handle complex elements, which optionally contain simple content -->
	<xsl:template name="element-complexType-forwardee" match="xsl:template[@name = 'element-complexType-forwardee']">
		<xsl:param name="root-document" /> <!-- contains root document -->
		<xsl:param name="root-path" /> <!-- contains path from root to included and imported documents -->
		<xsl:param name="root-namespaces" /> <!-- contains root document's namespaces and prefixes -->
		
		<xsl:param name="namespace-documents" /> <!-- contains all documents in element namespace -->
		<xsl:param name="namespace-prefix" /> <!-- contains inherited namespace prefix -->
		
		<xsl:param name="id" /> <!-- contains node name, or references node name in case of groups -->
		<xsl:param name="min-occurs" /> <!-- contains @minOccurs attribute (for referenced elements) -->
		<xsl:param name="max-occurs" /> <!-- contains @maxOccurs attribute (for referenced elements) -->
		<xsl:param name="simple" /> <!-- indicates if an element allows simple content -->
		<xsl:param name="choice" /> <!-- handles xs:choice elements and descendants; contains a unique ID for radio buttons of the same group to share -->
		<xsl:param name="disabled" /> <!-- is used to disable elements that are copies for additional occurrences -->
		<xsl:param name="xpath" /> <!-- contains an XPath query relative to the current node, to be used with xml document -->
		
		<xsl:param name="node" />
		
		<xsl:choose>
			<!-- if called from forward, call it again with with $node as calling node -->
			<xsl:when test="name() = 'xsl:template'">
				<xsl:for-each select="$node">
					<xsl:call-template name="element-complexType-forwardee">
						<xsl:with-param name="root-document" select="$root-document" />
						<xsl:with-param name="root-path" select="$root-path" />
						<xsl:with-param name="root-namespaces" select="$root-namespaces" />
						
						<xsl:with-param name="namespace-documents" select="$namespace-documents" />
						<xsl:with-param name="namespace-prefix" select="$namespace-prefix" />
						
						<xsl:with-param name="id" select="$id" />
						<xsl:with-param name="min-occurs" select="$min-occurs" />
						<xsl:with-param name="max-occurs" select="$max-occurs" />
						<xsl:with-param name="simple" select="$simple" />
						<xsl:with-param name="choice" select="$choice" />
						<xsl:with-param name="disabled" select="$disabled" />
						<xsl:with-param name="xpath" select="$xpath" />
					</xsl:call-template>
				</xsl:for-each>
			</xsl:when>
			<!-- else continue processing -->
			<xsl:otherwise>
				<xsl:call-template name="log">
					<xsl:with-param name="reference">xs:element[xs:complexType/*[not(self::xs:simpleContent)]]-forwardee</xsl:with-param>
				</xsl:call-template>
				
				<!-- add radio button if $choice is specified -->
				<xsl:if test="not($choice = '') and not($choice = 'true')">
					<xsl:call-template name="add-choice-button">
						<!-- $choice contains a unique id and is used for the options name -->
						<xsl:with-param name="name" select="$choice" />
						<xsl:with-param name="description">
							<xsl:if test="./xs:annotation/xs:documentation">
								<xsl:value-of select="./xs:annotation/xs:documentation/text()" />
							</xsl:if>
							<xsl:if test="not(./xs:annotation/xs:documentation)">
								<xsl:value-of select="@name" />
							</xsl:if>
						</xsl:with-param>
						<xsl:with-param name="disabled" select="$disabled" />
					</xsl:call-template>
				</xsl:if>
				
				<xsl:call-template name="handle-complex-elements">
					<xsl:with-param name="root-document" select="$root-document" />
					<xsl:with-param name="root-path" select="$root-path" />
					<xsl:with-param name="root-namespaces" select="$root-namespaces" />
					
					<xsl:with-param name="namespace-documents" select="$namespace-documents" />
					<xsl:with-param name="namespace-prefix" select="$namespace-prefix" />
					
					<xsl:with-param name="id" select="$id" />
					<xsl:with-param name="min-occurs" select="$min-occurs" />
					<xsl:with-param name="max-occurs" select="$max-occurs" />
					<xsl:with-param name="simple" select="$simple" />
					<xsl:with-param name="choice">
						<xsl:if test="not($choice = '')">true</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="disabled" select="$disabled" />
					<xsl:with-param name="xpath" select="$xpath" />
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
</xsl:stylesheet>
