<?php

namespace App\Controllers;

use App\Models\BlogModel;
use App\Controllers\BaseController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\CommentModel;
use App\Models\BlogCategoryModel;

class BlogController extends BaseController
{
    public function blogs()
    {
        $blogModel = new BlogModel();
        $blogCategoryModel = new BlogCategoryModel();

        $perPage = 6;
        $page = $this->request->getGet('page') ?? 1;
        $offset = ($page - 1) * $perPage;

        $selectedTags = $this->request->getGet('tags') ?? [];

        $categoriesWithTags = [];
        $categories = $blogCategoryModel->findAll();
        foreach ($categories as $category) {
            $categoryId = $category->id;
            $tags = $this->getTagsForCategory($categoryId);
            $categoriesWithTags[] = [
                'category' => $category,
                'tags' => $tags
            ];
        }

        if (!empty($selectedTags)) {
            foreach ($selectedTags as $tag) {
                $blogModel->orLike('tags', $tag);
            }
        }

        $totalBlogs = $blogModel->countAllResults(false);
        $blogs = $blogModel->orderBy('id', 'DESC')->findAll($perPage, $offset);

        $totalPages = ceil($totalBlogs / $perPage);

        $selectedTagsQuery = '';
        if (!empty($selectedTags)) {
            $selectedTagsQuery = '&' . http_build_query(['tags' => $selectedTags]);
        }

        $data = [
            'blogs' => $blogs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'categoriesWithTags' => $categoriesWithTags,
            'selectedTags' => $selectedTags,
            'selectedTagsQuery' => $selectedTagsQuery
        ];

        return view('blogs', $data);
    }

    protected function getTagsForCategory($categoryId)
    {
        $db = \Config\Database::connect();
        $query = $db->query("SELECT tags, COUNT(*) as count FROM blogs WHERE category = ? GROUP BY tags", [$categoryId]);
        return $query->getResult();
    }
    public function details($blogId)
    {
        $blogModel = new BlogModel();
        $data['blog'] = $blogModel->find($blogId);

        $commentModel = new CommentModel();
        $data['comments'] = $commentModel->getCommentsByBlogId($blogId);

        foreach ($data['comments'] as $comment) {
            $comment->replies = $commentModel->getRepliesByParentId($comment->comment_id);
        }

        return view('blog_details', $data);
    }

    public function replies($blogId)
    {
        $blogModel = new BlogModel();
        $data['blog'] = $blogModel->find($blogId);

        $commentModel = new CommentModel();
        $data['comments'] = $commentModel->getCommentsByBlogId($blogId);
        echo json_encode($data);
    }

    public function addComment()
    {
        print_r($this->request->getPost('comment'));
        //$blogId = $this->request->getPost('blog_id');
        $comment_id = $this->request->getPost('comment_id');
        $comment = $this->request->getPost('comment');
        $blogId = $this->request->getPost('blogId');


        if (empty($blogId) || empty($comment) || empty($comment_id)) {
            echo 'error';
            //return redirect()->back()->withInput()->with('error', 'All fields are required.');
        }

        $commentModel = new CommentModel();
        $commentModel->insertComment($blogId, $comment_id, $comment);
        // return redirect()->to(base_url('blogs/details/' . $blogId));
    }

    public function addReply($commentId)
    {
        $parentCommentId = $commentId;
        $replyName = $this->request->getPost('reply_name');
        $replyComment = $this->request->getPost('reply_comment');

        if (empty($replyName) || empty($replyComment)) {
            return redirect()->back()->withInput()->with('error', 'All fields are required.');
        }

        $commentModel = new CommentModel();
        $commentModel->insertReply($parentCommentId, $replyName, $replyComment);

        $commentModel = new CommentModel();
        $comment = $commentModel->find($commentId);

        if ($comment && isset($comment['blog_id'])) {
            $blogId = $comment['blog_id'];

            return redirect()->to(base_url('blogs/details/' . $blogId));
        } else {
            return redirect()->back()->with('error', 'Failed to retrieve blog details.');
        }
    }


}
