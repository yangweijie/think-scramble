<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 文章模型
 * 
 * @property int $id 文章ID
 * @property string $title 标题
 * @property string $content 内容
 * @property string $summary 摘要
 * @property int $user_id 作者ID
 * @property int $category_id 分类ID
 * @property int $status 状态 (0:草稿 1:发布 2:下线)
 * @property int $views 浏览量
 * @property int $likes 点赞数
 * @property string $tags 标签
 * @property string $cover_image 封面图片
 * @property string $published_at 发布时间
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class ArticleModel extends Model
{
    /**
     * 表名
     */
    protected $table = 'articles';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 字段类型定义
     */
    protected $type = [
        'id' => 'integer',
        'title' => 'string',
        'content' => 'string',
        'summary' => 'string',
        'user_id' => 'integer',
        'category_id' => 'integer',
        'status' => 'integer',
        'views' => 'integer',
        'likes' => 'integer',
        'tags' => 'string',
        'cover_image' => 'string',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 字段 Schema 定义
     */
    protected $schema = [
        'id' => [
            'type' => 'int',
            'comment' => '文章ID',
        ],
        'title' => [
            'type' => 'varchar(200)',
            'comment' => '标题',
        ],
        'content' => [
            'type' => 'longtext',
            'comment' => '内容',
        ],
        'summary' => [
            'type' => 'text',
            'comment' => '摘要',
        ],
        'user_id' => [
            'type' => 'int',
            'comment' => '作者ID',
        ],
        'category_id' => [
            'type' => 'int',
            'comment' => '分类ID',
        ],
        'status' => [
            'type' => 'tinyint',
            'comment' => '状态',
            'default' => 0,
        ],
        'views' => [
            'type' => 'int',
            'comment' => '浏览量',
            'default' => 0,
        ],
        'likes' => [
            'type' => 'int',
            'comment' => '点赞数',
            'default' => 0,
        ],
        'tags' => [
            'type' => 'varchar(500)',
            'comment' => '标签',
        ],
        'cover_image' => [
            'type' => 'varchar(255)',
            'comment' => '封面图片',
        ],
        'published_at' => [
            'type' => 'datetime',
            'comment' => '发布时间',
        ],
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'title' => 'require|length:1,200',
        'content' => 'require',
        'summary' => 'length:0,500',
        'user_id' => 'require|integer',
        'category_id' => 'integer',
        'status' => 'in:0,1,2',
        'tags' => 'length:0,500',
    ];

    /**
     * 验证消息
     */
    protected $message = [
        'title.require' => '标题不能为空',
        'title.length' => '标题长度不能超过200个字符',
        'content.require' => '内容不能为空',
        'summary.length' => '摘要长度不能超过500个字符',
        'user_id.require' => '作者ID不能为空',
        'user_id.integer' => '作者ID必须是整数',
        'category_id.integer' => '分类ID必须是整数',
        'status.in' => '状态值不正确',
        'tags.length' => '标签长度不能超过500个字符',
    ];

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
     * 获取文章作者
     * 
     * @belongsTo UserModel
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }

    /**
     * 获取文章分类
     * 
     * @belongsTo CategoryModel
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id');
    }

    /**
     * 获取文章评论
     * 
     * @hasMany CommentModel
     * @return \think\model\relation\HasMany
     */
    public function comments()
    {
        return $this->hasMany(CommentModel::class, 'article_id', 'id');
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
            0 => '草稿',
            1 => '发布',
            2 => '下线',
        ];

        return $statusMap[$value] ?? '未知';
    }

    /**
     * 标签访问器
     *
     * @param string $value
     * @return array
     */
    public function getTagsArrayAttr(string $value): array
    {
        return empty($value) ? [] : explode(',', $value);
    }

    /**
     * 标签修改器
     *
     * @param array $value
     * @return string
     */
    public function setTagsAttr(array $value): string
    {
        return implode(',', $value);
    }

    /**
     * 封面图片访问器
     *
     * @param string $value
     * @return string
     */
    public function getCoverImageAttr(string $value): string
    {
        if (empty($value)) {
            return '/default-cover.jpg';
        }

        if (strpos($value, 'http') === 0) {
            return $value;
        }

        return '/uploads/covers/' . $value;
    }

    /**
     * 已发布文章查询
     *
     * @param \think\db\Query $query
     * @return void
     */
    public function scopePublished($query): void
    {
        $query->where('status', 1);
    }

    /**
     * 热门文章查询
     *
     * @param \think\db\Query $query
     * @param int $minViews
     * @return void
     */
    public function scopePopular($query, int $minViews = 100): void
    {
        $query->where('views', '>=', $minViews);
    }

    /**
     * 按分类查询
     *
     * @param \think\db\Query $query
     * @param int $categoryId
     * @return void
     */
    public function scopeByCategory($query, int $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }

    /**
     * 增加浏览量
     *
     * @return bool
     */
    public function incrementViews(): bool
    {
        return $this->inc('views')->save();
    }

    /**
     * 增加点赞数
     *
     * @return bool
     */
    public function incrementLikes(): bool
    {
        return $this->inc('likes')->save();
    }

    /**
     * 发布文章
     *
     * @return bool
     */
    public function publish(): bool
    {
        $this->status = 1;
        $this->published_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 下线文章
     *
     * @return bool
     */
    public function unpublish(): bool
    {
        $this->status = 2;
        return $this->save();
    }
}
