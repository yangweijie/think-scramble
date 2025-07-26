<?php

declare(strict_types=1);

namespace app\controller;

use think\Request;
use think\Response;
use think\annotation\Route;
use think\annotation\route\Middleware;

/**
 * 安全控制器示例
 * 
 * 演示中间件分析功能
 * 
 * @middleware auth
 * @middleware throttle:60,1
 */
class SecureController
{
    /**
     * 获取用户信息
     * 
     * @Route("users/profile", method="GET")
     * @return Response
     */
    public function profile(): Response
    {
        return json([
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);
    }

    /**
     * 更新用户信息
     * 
     * @Route("users/profile", method="PUT")
     * @middleware csrf
     * @param Request $request
     * @return Response
     */
    public function updateProfile(Request $request): Response
    {
        $data = $request->put();
        
        // 更新用户信息逻辑
        
        return json(['message' => '更新成功']);
    }

    /**
     * 管理员专用接口
     * 
     * @Route("admin/users", method="GET")
     * @middleware admin
     * @middleware log:admin_access
     * @return Response
     */
    public function adminUsers(): Response
    {
        return json([
            'users' => [
                ['id' => 1, 'username' => 'user1'],
                ['id' => 2, 'username' => 'user2'],
            ],
        ]);
    }

    /**
     * 公开接口（无需认证）
     * 
     * @Route("public/info", method="GET")
     * @middleware cors
     * @return Response
     */
    public function publicInfo(): Response
    {
        return json([
            'app_name' => 'ThinkScramble Demo',
            'version' => '1.0.0',
        ]);
    }

    /**
     * 高频率限制接口
     * 
     * @Route("api/data", method="GET")
     * @middleware throttle:10,1
     * @middleware cache:300
     * @return Response
     */
    public function apiData(): Response
    {
        return json([
            'data' => 'sensitive data',
            'timestamp' => time(),
        ]);
    }

    /**
     * OAuth2 保护的接口
     * 
     * @Route("oauth/resource", method="GET")
     * @middleware oauth2:read,write
     * @return Response
     */
    public function oauthResource(): Response
    {
        return json([
            'resource' => 'protected resource',
            'scopes' => ['read', 'write'],
        ]);
    }

    /**
     * API Key 保护的接口
     * 
     * @Route("api/key-protected", method="GET")
     * @middleware api_key
     * @return Response
     */
    public function apiKeyProtected(): Response
    {
        return json([
            'message' => 'API Key 验证成功',
            'data' => 'protected data',
        ]);
    }

    /**
     * 会话保护的接口
     * 
     * @Route("session/data", method="GET")
     * @middleware session
     * @return Response
     */
    public function sessionData(): Response
    {
        return json([
            'session_id' => session_id(),
            'user_data' => 'session protected data',
        ]);
    }

    /**
     * 多重保护接口
     * 
     * @Route("secure/multi", method="POST")
     * @middleware auth
     * @middleware admin
     * @middleware csrf
     * @middleware throttle:5,1
     * @middleware log:critical_action
     * @param Request $request
     * @return Response
     */
    public function multiSecure(Request $request): Response
    {
        return json([
            'message' => '多重安全验证通过',
            'action' => 'critical operation completed',
        ]);
    }

    /**
     * 自定义中间件示例
     * 
     * @Route("custom/protected", method="GET")
     * @middleware custom_auth:level1
     * @middleware ip_whitelist
     * @middleware request_signature
     * @return Response
     */
    public function customProtected(): Response
    {
        return json([
            'message' => '自定义安全验证通过',
            'protection_level' => 'high',
        ]);
    }

    /**
     * 角色权限控制
     * 
     * @Route("roles/{role}/permissions", method="GET")
     * @middleware auth
     * @middleware role:admin,manager
     * @middleware permission:view_roles
     * @param string $role
     * @return Response
     */
    public function rolePermissions(string $role): Response
    {
        return json([
            'role' => $role,
            'permissions' => [
                'view_users',
                'edit_users',
                'delete_users',
            ],
        ]);
    }

    /**
     * 文件上传（带安全检查）
     * 
     * @Route("upload/secure", method="POST")
     * @middleware auth
     * @middleware csrf
     * @middleware file_scan
     * @middleware virus_check
     * @param Request $request
     * @return Response
     */
    public function secureUpload(Request $request): Response
    {
        $file = $request->file('file');
        
        if (!$file) {
            return json(['error' => '未选择文件'], 400);
        }
        
        // 安全文件上传逻辑
        
        return json([
            'message' => '文件上传成功',
            'filename' => $file->getOriginalName(),
        ]);
    }

    /**
     * 数据导出（需要特殊权限）
     * 
     * @Route("export/data", method="GET")
     * @middleware auth
     * @middleware permission:export_data
     * @middleware audit_log:data_export
     * @middleware rate_limit:1,60
     * @return Response
     */
    public function exportData(): Response
    {
        return json([
            'export_url' => '/downloads/data_export_' . date('Y-m-d') . '.csv',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
        ]);
    }

    /**
     * 系统监控接口
     * 
     * @Route("monitor/status", method="GET")
     * @middleware auth
     * @middleware admin
     * @middleware monitor_access
     * @return Response
     */
    public function monitorStatus(): Response
    {
        return json([
            'status' => 'healthy',
            'uptime' => '99.9%',
            'last_check' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 批量操作接口
     * 
     * @Route("batch/operation", method="POST")
     * @middleware auth
     * @middleware csrf
     * @middleware batch_limit:100
     * @middleware operation_log
     * @param Request $request
     * @return Response
     */
    public function batchOperation(Request $request): Response
    {
        $operations = $request->post('operations', []);
        
        return json([
            'processed' => count($operations),
            'success' => true,
            'batch_id' => uniqid('batch_'),
        ]);
    }

    /**
     * 实时通知接口
     * 
     * @Route("notifications/realtime", method="GET")
     * @middleware auth
     * @middleware websocket_auth
     * @middleware connection_limit:1000
     * @return Response
     */
    public function realtimeNotifications(): Response
    {
        return json([
            'websocket_url' => 'wss://example.com/notifications',
            'auth_token' => 'temp_token_' . time(),
        ]);
    }
}
