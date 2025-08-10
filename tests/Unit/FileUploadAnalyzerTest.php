<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yangweijie\ThinkScramble\Analyzer\FileUploadAnalyzer;

/**
 * @covers \Yangweijie\ThinkScramble\Analyzer\FileUploadAnalyzer
 */
class FileUploadAnalyzerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_generate_openapi_request_body_property()
    {
        $analyzer = new FileUploadAnalyzer();
        
        $fileUpload = [
            'name' => 'avatar',
            'required' => true,
            'description' => '用户头像文件',
            'allowed_types' => ['jpg', 'png', 'gif'],
            'max_size' => 2097152, // 2MB
        ];
        
        $property = $analyzer->generateOpenApiRequestBodyProperty($fileUpload);
        
        $this->assertEquals('string', $property['type']);
        $this->assertEquals('binary', $property['format']);
        $this->assertStringContainsString('用户头像文件', $property['description']);
        $this->assertStringContainsString('jpg, png, gif', $property['description']);
        $this->assertStringContainsString('2MB', $property['description']);
    }
    
    /**
     * @test
     */
    public function it_can_generate_openapi_parameter()
    {
        $analyzer = new FileUploadAnalyzer();
        
        $fileUpload = [
            'name' => 'document',
            'required' => false,
            'description' => '文档文件',
        ];
        
        $parameter = $analyzer->generateOpenApiParameter($fileUpload);
        
        $this->assertEquals('document', $parameter['name']);
        $this->assertEquals('formData', $parameter['in']);
        $this->assertFalse($parameter['required']);
        $this->assertEquals('string', $parameter['schema']['type']);
        $this->assertEquals('binary', $parameter['schema']['format']);
    }
}