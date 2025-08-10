<?php

declare(strict_types=1);

namespace app\controller;

use think\Request;
use think\Response;

/**
 * 文件上传控制器示例
 */
class UploadController
{
    /**
     * 上传单个文件
     * 
     * @param Request $request
     * @return Response
     * 
     * @upload avatar required jpg,png,gif max:2MB 用户头像文件
     * @param {file} avatar 头像文件
     */
    public function uploadAvatar(Request $request): Response
    {
        $avatar = $request->file('avatar');
        $userId = $request->param('user_id');
        
        if (!$avatar || !$avatar->isValid()) {
            return json(['error' => '文件上传失败'], 400);
        }
        
        return json([
            'avatar_url' => '/uploads/avatar.jpg',
            'user_id' => $userId,
            'filename' => $avatar->getOriginalName(),
            'size' => $avatar->getSize()
        ]);
    }

    /**
     * 批量上传文件
     * 
     * @param Request $request
     * @return Response
     * 
     * @upload files required 支持多文件上传
     * @param string category 文件分类
     */
    public function batchUpload(Request $request): Response
    {
        $files = $request->file('files');
        $category = $request->param('category', 'general');
        
        $results = [];
        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $results[] = [
                        'url' => '/uploads/' . $file->getOriginalName(),
                        'filename' => $file->getOriginalName(),
                        'size' => $file->getSize()
                    ];
                }
            }
        }
        
        return json([
            'category' => $category,
            'files' => $results,
            'total' => count($results)
        ]);
    }

    /**
     * 上传文档
     * 
     * @param Request $request
     * @return Response
     * 
     * @file document pdf,doc,docx,xls,xlsx max:50MB 文档文件
     * @param string title 文档标题
     * @param string description 文档描述
     */
    public function uploadDocument(Request $request): Response
    {
        $document = $request->file('document');
        $title = $request->param('title');
        $description = $request->param('description', '');
        
        if (!$document || !$document->isValid()) {
            return json(['error' => '文档上传失败'], 400);
        }
        
        return json([
            'document_id' => uniqid(),
            'title' => $title,
            'description' => $description,
            'download_url' => '/downloads/' . $document->getOriginalName(),
            'preview_url' => '/preview/' . $document->getOriginalName(),
            'file_size' => $document->getSize(),
            'mime_type' => $document->getMime()
        ]);
    }

    /**
     * 混合参数上传
     * 
     * @param Request $request
     * @return Response
     * 
     * 这个方法通过代码分析自动识别文件上传参数
     */
    public function mixedUpload(Request $request): Response
    {
        // 这些调用会被自动识别为文件上传参数
        $image = $request->file('image');
        $thumbnail = $request->file('thumbnail');
        
        // 普通参数
        $name = $request->param('name');
        $tags = $request->param('tags', []);
        
        $result = [
            'name' => $name,
            'tags' => $tags,
            'files' => []
        ];
        
        if ($image && $image->isValid()) {
            $result['files']['image'] = [
                'url' => '/uploads/images/' . $image->getOriginalName(),
                'size' => $image->getSize()
            ];
        }
        
        if ($thumbnail && $thumbnail->isValid()) {
            $result['files']['thumbnail'] = [
                'url' => '/uploads/thumbnails/' . $thumbnail->getOriginalName(),
                'size' => $thumbnail->getSize()
            ];
        }
        
        return json($result);
    }

    /**
     * 获取上传配置
     * 
     * @return Response
     */
    public function getUploadConfig(): Response
    {
        return json([
            'max_file_size' => '10MB',
            'allowed_types' => [
                'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
                'archive' => ['zip', 'rar', '7z', 'tar', 'gz']
            ],
            'upload_path' => '/uploads/',
            'temp_path' => '/temp/'
        ]);
    }

    /**
     * 使用 OpenAPI 3.0 规范的文件上传示例
     * 
     * @param Request $request
     * @return Response
     * 
     * @upload cover required jpg,png max:5MB 封面图片
     * @upload attachment pdf,doc,docx max:20MB 附件文件
     * @param string title 文章标题
     * @param string content 文章内容
     */
    public function openApi3Upload(Request $request): Response
    {
        $cover = $request->file('cover');
        $attachment = $request->file('attachment');
        $title = $request->param('title');
        $content = $request->param('content');
        
        // 处理文件上传逻辑
        
        return json([
            'message' => '文件上传成功',
            'article_id' => uniqid(),
            'title' => $title,
            'cover_url' => '/uploads/' . $cover->getOriginalName(),
            'attachment_url' => '/uploads/' . $attachment->getOriginalName()
        ]);
    }
}
