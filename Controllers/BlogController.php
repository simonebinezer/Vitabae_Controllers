<?php

namespace App\Controllers;

use App\Models\BlogModel;
use App\Models\ExistingTagModel;
use CodeIgniter\Controller;

class BlogController extends Controller
{
    public function index()
    {
        $blogModel = new BlogModel();
        $tagModel = new ExistingTagModel();

        $groupedTags = $tagModel->getTagsGroupedByHeader();
        $selectedTags = $this->request->getGet('tags') ?: [];
        $page = $this->request->getGet('page') ?: 1;
        $perPage = 8;

        $blogs = $blogModel->getBlogs($selectedTags);
        $totalBlogs = count($blogs);
        $blogs = array_slice($blogs, ($page - 1) * $perPage, $perPage);
        $totalPages = ceil($totalBlogs / $perPage);

        $todaysPick = $blogModel->getTodaysPick();

        foreach ($blogs as &$blog) {
            if (is_string($blog['images'])) {
                $blog['images'] = json_decode($blog['images'], true);
            }
            $blog['tags'] = $this->getTagsByContentId($blog['id']);
            $uniqueTags = [];
            foreach ($blog['tags'] as $tag) {
                $tagPart = explode('/', $tag['path'])[0];
                if (!in_array($tagPart, $uniqueTags)) {
                    $uniqueTags[] = $tagPart;
                }
            }
            $blog['tagGr'] = implode(', ', $uniqueTags);

            if (!isset($blog['read'])) {
                $blog['read'] = '0';
            }
            $additionalValue = $blogModel->getFieldValue($blog['id'], 12);
            if ($additionalValue) {
                $blog['additional_value'] = $additionalValue;
            } else {
                $blog['additional_value'] = 'No additional value';
            }
        }

        foreach ($todaysPick as &$pick) {
            if (is_string($pick['images'])) {
                $pick['images'] = json_decode($pick['images'], true);
            }
            if (!isset($pick['read'])) {
                $pick['read'] = '0';
            }
        }

        return view('blogs', [
            'blogs' => $blogs ?? [],
            'groupedTags' => $groupedTags,
            'selectedTags' => $selectedTags,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'todaysPick' => $todaysPick ?? [],
            'totalBlogs' => $totalBlogs
        ]);
    }
    

    public function getTagsByContentId($content_id)
    {
        $tagModel = new ExistingTagModel();
        return $tagModel->getTagsByContentId($content_id);
    }
    public function details($additionalValue)
    {
        $blogModel = new BlogModel();
        $tagModel = new ExistingTagModel();

        $blog = $blogModel->getBlogByAdditionalValue($additionalValue);
        if (!$blog) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Blog not found');
        }
        if (isset($blog['images']) && is_string($blog['images'])) {
            $blog['images'] = json_decode($blog['images'], true);
            if (!is_array($blog['images'])) {
                $blog['images'] = [];
            }
        } else {
            $blog['images'] = [];
        }

        $id = $blog['id'] ? $blog['id'] : 0;

        $relatedPosts = $blogModel->getRelatedPosts($id);
        $tagsAboveTitle = $tagModel->getTagsByContentId($id);
        $blogs = $blogModel->getBlog($id);

        $read = $blogs['read'] ?? null;
        $articles = $blogs['articles'] ?? '';
        $blog_posting = $blogs['blog_posting'] ?? '';
        $faq = $blogs['faq'] ?? '';
        $authorDescription = $blogs['authorDescription'] ?? null;
        $linkedIn = $blogs['linkedIn'] ?? null;
        $browsertitle = $blogs['browsertitle'] ?? 'Default Browser Title';
        $userimg = isset($blogs['userimg']) && is_string($blogs['userimg']) ? json_decode($blogs['userimg'], true) : [];
        $userimgUrl = is_array($userimg) ? ($userimg['imagefile'] ?? '') : $userimg;

        if (is_string($blogs['images'])) {
            $blogs['images'] = json_decode($blogs['images'], true);
        }
        if (is_string($blogs['metadata'])) {
            $metadata = json_decode($blogs['metadata'], true);
            $author = $metadata['author'] ?? '';
            $robots = $metadata['robots'] ?? '';
        } else {
            $author = '';
            $robots = '';
        }
        
        $result = [
            "images" => $blogs['images'],
            "title" => $blogs['title'],
            "metadesc" => $blogs['metadesc'],
            "metakey" => $blogs['metakey'],
            "publish_up" => $blogs['publish_up'],
            "id" => $blogs['id'],
            "introtext" => $blogs['introtext'],
            "read" => $read,
            "modified" => $blogs['modified'],
            "browsertitle" => $browsertitle,
            "userimg" => $userimgUrl,
            "articles" => $articles,
            "blog_posting" => $blog_posting,
            "faq" => $faq,
            "authorDescription" => $authorDescription,
            "linkedIn" => $linkedIn
        ];
// var_dump($result);
        return view('blog_details', [
            'blog' => $result,
            'relatedPosts' => $relatedPosts,
            'tagsAboveTitle' => $tagsAboveTitle,
            'author' => $author,
            'robots' => $robots,
        ]);
    }
}
