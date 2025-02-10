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
                $blog['read'] = '0'; // Default read time if not set
            }
             // Fetch additional value

             $additionalValue = $blogModel->getFieldValue($blog['id'], 5);

             if ($additionalValue) {
 
                 $blog['additional_value'] = $additionalValue;
 
             } else {
 
                 $blog['additional_value'] = 'No additional value'; // Default value if not set
 
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

    // Ensure 'images' is a valid array or set a default value
    if (isset($blog['images']) && is_string($blog['images'])) {
        $blog['images'] = json_decode($blog['images'], true);
        if (!is_array($blog['images'])) {
            $blog['images'] = []; // Default to empty array if decoding fails
        }
    } else {
        $blog['images'] = []; // Default to empty array if images is null or not a string
    }
        $id = $blog['id'] ? $blog['id']: 0;


        $relatedPosts = $blogModel->getRelatedPosts($id);
        $tagsAboveTitle = $tagModel->getTagsByContentId($id);
        $read=null;
        // $twitter = null;
        // $facebook =null;
        // $gmail = null;
        $userimg = null;
        $browsertitle = null;
        $blogs = $blogModel->getBlog($id);
        $relatedPosts = $blogModel->getRelatedPosts($id);
        foreach ($blogs as $key => $blog) {
            # code...
            
            switch ($blog["field_id"]) {
                case '1':
                    # code...
                    $read = $blog['value'];

                    break;
                case '5':
                    # code...
                    $gmail = $blog['value'];
                    break;
                case '7':
                    # code...
                    $userimg = json_decode($blog['value'], true);
                    break;
                case '6':
                    # code...
                    $browsertitle = $blog['value'];
                    break;
                default:
                    # code...
                    break;
            }
        }

        if (is_string($blogs[0]['images'])) {
            // echo "before";

            // print_r($blog[0]['images']);
            $blogs[0]['images'] = json_decode($blogs[0]['images'], true);
            // echo "after";

            // print_r($blog[0]['images']);
        }

        if (is_string($blogs[0]['metadata'])) {
            $metadata = json_decode($blogs[0]['metadata'], true);
            $author = $metadata['author'] ?? '';
            $robots = $metadata['robots'] ?? '';
        } else {
            $author = '';
        }

        $result = [

            "images" => $blogs[0]['images'],
            "title" => $blogs[0]['title'],
            "metadesc" => $blogs[0]['metadesc'],
            "metakey" => $blogs[0]['metakey'],
            "publish_up" => $blogs[0]['publish_up'],

            "id" => $blogs[0]['id'],

            "introtext" => $blogs[0]['introtext'],

            "read" => $read,

            "modified" => $blogs[0]['modified'],

            "browsertitle" => $browsertitle,

            "userimg" => isset($userimg)? $userimg['imagefile']:$userimg,
        ];
        return view('blog_details', [
            'blog' => $result,
            'relatedPosts' => $relatedPosts,
            'tagsAboveTitle' => $tagsAboveTitle,
            'author' => $author,
            'robots' => $robots
        ]);
    }
}
