<?php

declare(strict_types=1);

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 用户模型
 * 
 * @property int $id 用户ID
 * @property string $username 用户名
 * @property string $email 邮箱地址
 * @property string $password 密码
 * @property string $nickname 昵称
 * @property int $age 年龄
 * @property int $status 状态 (0:禁用 1:启用)
 * @property string $avatar 头像URL
 * @property string $phone 手机号
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $deleted_at 删除时间
 */
class UserModel extends Model
{
    use SoftDelete;

    /**
     * 表名
     */
    protected $table = 'users';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 字段类型定义
     */
    protected $type = [
        'id' => 'integer',
        'username' => 'string',
        'email' => 'string',
        'password' => 'string',
        'nickname' => 'string',
        'age' => 'integer',
        'status' => 'integer',
        'avatar' => 'string',
        'phone' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 字段 Schema 定义
     */
    protected $schema = [
        'id' => [
            'type' => 'int',
            'comment' => '用户ID',
        ],
        'username' => [
            'type' => 'varchar(50)',
            'comment' => '用户名',
        ],
        'email' => [
            'type' => 'varchar(100)',
            'comment' => '邮箱地址',
        ],
        'password' => [
            'type' => 'varchar(255)',
            'comment' => '密码',
        ],
        'nickname' => [
            'type' => 'varchar(50)',
            'comment' => '昵称',
        ],
        'age' => [
            'type' => 'int',
            'comment' => '年龄',
        ],
        'status' => [
            'type' => 'tinyint',
            'comment' => '状态',
            'default' => 1,
        ],
        'avatar' => [
            'type' => 'varchar(255)',
            'comment' => '头像URL',
        ],
        'phone' => [
            'type' => 'varchar(20)',
            'comment' => '手机号',
        ],
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'username' => 'require|length:3,50|alphaNum|unique:users',
        'email' => 'require|email|unique:users',
        'password' => 'require|length:6,20',
        'nickname' => 'length:2,50',
        'age' => 'number|between:1,120',
        'status' => 'in:0,1',
        'phone' => 'mobile',
    ];

    /**
     * 验证消息
     */
    protected $message = [
        'username.require' => '用户名不能为空',
        'username.length' => '用户名长度必须在3-50个字符之间',
        'username.alphaNum' => '用户名只能包含字母和数字',
        'username.unique' => '用户名已存在',
        'email.require' => '邮箱不能为空',
        'email.email' => '邮箱格式不正确',
        'email.unique' => '邮箱已存在',
        'password.require' => '密码不能为空',
        'password.length' => '密码长度必须在6-20个字符之间',
        'nickname.length' => '昵称长度必须在2-50个字符之间',
        'age.number' => '年龄必须是数字',
        'age.between' => '年龄必须在1-120之间',
        'status.in' => '状态值不正确',
        'phone.mobile' => '手机号格式不正确',
    ];

    /**
     * 隐藏字段
     */
    protected $hidden = ['password'];

    /**
     * 只读字段
     */
    protected $readonly = ['id', 'created_at'];

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 软删除时间字段
     */
    protected $deleteTime = 'deleted_at';

    /**
     * 获取用户的文章
     * 
     * @hasMany ArticleModel
     * @return \think\model\relation\HasMany
     */
    public function articles()
    {
        return $this->hasMany(ArticleModel::class, 'user_id', 'id');
    }

    /**
     * 获取用户的个人资料
     * 
     * @hasOne ProfileModel
     * @return \think\model\relation\HasOne
     */
    public function profile()
    {
        return $this->hasOne(ProfileModel::class, 'user_id', 'id');
    }

    /**
     * 获取用户的角色
     * 
     * @belongsToMany RoleModel
     * @return \think\model\relation\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(RoleModel::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * 密码修改器
     *
     * @param string $value
     * @return string
     */
    public function setPasswordAttr(string $value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * 状态访问器
     *
     * @param int $value
     * @return string
     */
    public function getStatusTextAttr(int $value): string
    {
        $statusMap = [
            0 => '禁用',
            1 => '启用',
        ];

        return $statusMap[$value] ?? '未知';
    }

    /**
     * 头像访问器
     *
     * @param string $value
     * @return string
     */
    public function getAvatarAttr(string $value): string
    {
        if (empty($value)) {
            return '/default-avatar.png';
        }

        if (strpos($value, 'http') === 0) {
            return $value;
        }

        return '/uploads/avatars/' . $value;
    }

    /**
     * 年龄范围查询
     *
     * @param \think\db\Query $query
     * @param int $minAge
     * @param int $maxAge
     * @return void
     */
    public function scopeAgeRange($query, int $minAge, int $maxAge): void
    {
        $query->where('age', 'between', [$minAge, $maxAge]);
    }

    /**
     * 活跃用户查询
     *
     * @param \think\db\Query $query
     * @return void
     */
    public function scopeActive($query): void
    {
        $query->where('status', 1);
    }

    /**
     * 按邮箱查找用户
     *
     * @param string $email
     * @return UserModel|null
     */
    public static function findByEmail(string $email): ?UserModel
    {
        return self::where('email', $email)->find();
    }

    /**
     * 按用户名查找用户
     *
     * @param string $username
     * @return UserModel|null
     */
    public static function findByUsername(string $username): ?UserModel
    {
        return self::where('username', $username)->find();
    }

    /**
     * 获取用户统计信息
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'articles_count' => $this->articles()->count(),
            'created_days' => (new \DateTime())->diff(new \DateTime($this->created_at))->days,
            'last_login' => $this->profile->last_login ?? null,
        ];
    }
}
