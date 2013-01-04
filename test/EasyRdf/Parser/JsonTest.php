<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2010 Nicholas J Humfrey.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author 'Nicholas J Humfrey" may be used to endorse or
 *    promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2010 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @version    $Id$
 */

require_once dirname(dirname(dirname(__FILE__))).
             DIRECTORY_SEPARATOR.'TestHelper.php';

class EasyRdf_Parser_JsonTest extends EasyRdf_TestCase
{
    protected $_parser = null;
    protected $_graph = null;
    protected $_data = null;

    public function setUp()
    {
        $this->_graph = new EasyRdf_Graph();
        $this->_parser = new EasyRdf_Parser_Json();
    }

    public function testParse()
    {
        $data = readFixture('foaf.json');
        $count = $this->_parser->parse($this->_graph, $data, 'json', null);
        $this->assertSame(14, $count);

        $joe = $this->_graph->resource('http://www.example.com/joe#me');
        $this->assertNotNull($joe);
        $this->assertClass('EasyRdf_Resource', $joe);
        $this->assertSame('http://www.example.com/joe#me', $joe->getUri());

        $name = $joe->get('foaf:name');
        $this->assertNotNull($name);
        $this->assertClass('EasyRdf_Literal', $name);
        $this->assertSame('Joe Bloggs', $name->getValue());
        $this->assertSame('en', $name->getLang());
        $this->assertSame(null, $name->getDatatype());

        $project = $joe->get('foaf:currentProject');
        $this->assertNotNull($project);
        $this->assertClass('EasyRdf_Resource', $project);
        $this->assertSame('_:genid1', $project->getUri());
    }

    public function testParseJsonTriples()
    {
        $data = readFixture('foaf.json-triples');
        $count = $this->_parser->parse($this->_graph, $data, 'json', null);
        $this->assertSame(14, $count);

        $joe = $this->_graph->resource('http://www.example.com/joe#me');
        $this->assertNotNull($joe);
        $this->assertClass('EasyRdf_Resource', $joe);
        $this->assertSame('http://www.example.com/joe#me', $joe->getUri());

        $name = $joe->get('foaf:name');
        $this->assertNotNull($name);
        $this->assertClass('EasyRdf_Literal', $name);
        $this->assertSame('Joe Bloggs', $name->getValue());
        $this->assertSame('en', $name->getLang());
        $this->assertSame(null, $name->getDatatype());

        $project = $joe->get('foaf:currentProject');
        $this->assertNotNull($project);
        $this->assertClass('EasyRdf_Resource', $project);
        $this->assertSame('_:genid1', $project->getUri());
    }

    public function testParseWithFormatObject()
    {
        $data = readFixture('foaf.json');
        $format = EasyRdf_Format::getFormat('json');
        $count = $this->_parser->parse($this->_graph, $data, $format, null);
        $this->assertSame(14, $count);

        $joe = $this->_graph->resource('http://www.example.com/joe#me');
        $this->assertStringEquals('Joe Bloggs', $joe->get('foaf:name'));
    }

    public function testParseBadJson()
    {
        # Test parsing JSON with 'bad' bnode identifiers
        $data = readFixture('foaf.bad-json');
        $count = $this->_parser->parse($this->_graph, $data, 'json', 'http://www.bbc.co.uk/');
        $this->assertSame(14, $count);

        $joe = $this->_graph->resource('http://www.example.com/joe#me');
        $this->assertStringEquals('Joe Bloggs', $joe->get('foaf:name'));

        $project = $joe->get('foaf:currentProject');
        $this->assertNotNull($project);
        $this->assertTrue($project->isBnode());
        $this->assertStringEquals("Joe's Current Project", $project->label());

        # Test going the other way
        $project2 = $this->_graph->resource('foaf:Project')->get('^rdf:type');
        $this->assertNotNull($project2);
        $this->assertTrue($project2->isBnode());
        $this->assertStringEquals("Joe's Current Project", $project2->label());

        $joe2 = $project2->get('^foaf:currentProject');
        $this->assertNotNull($joe2);
        $this->assertStringEquals('Joe Bloggs', $joe2->get('foaf:name'));
    }

    public function testParseJsonSyntaxError()
    {
        if (version_compare(PHP_VERSION, "5.3.0") >= 0) {
            $this->setExpectedException(
                'EasyRdf_Exception',
                'JSON Parse syntax error'
            );
        } else {
            $this->setExpectedException(
                'EasyRdf_Exception',
                'JSON Parse error'
            );
        }
        $this->_parser->parse(
            $this->_graph, '{ "foo":"bar"', 'json', 'http://www.example.com/'
        );
    }

    public function testParseEmpty()
    {
        $count = $this->_parser->parse($this->_graph, '{}', 'json', null);
        $this->assertSame(0, $count);

        // Should be empty but no exception thrown
        $this->assertSame(0, $this->_graph->countTriples());
    }

    public function testParseUnsupportedFormat()
    {
        $this->setExpectedException(
            'EasyRdf_Exception',
            'EasyRdf_Parser_Json does not support: unsupportedformat'
        );
        $rdf = $this->_parser->parse(
            $this->_graph, $this->_data, 'unsupportedformat', null
        );
    }
}
