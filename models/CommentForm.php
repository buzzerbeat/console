<?php 

namespace console\models;

use yii\base\Model;
use common\models\User;
use common\models\Comment;
use common\models\ImageForm;
use console\models\CommentOrigin;
use console\models\UserSearch;
use common\components\Utility;

class CommentForm extends Model{
	public $content;
	public $create_time;
	public $uname;
	public $uavatar;
	public $item_id;
	public $item_type;
	public $client;
	public $site;
	public $dig;
	public $is_hot;
	
	public $filter = ['回复', 'http', '@', '微信'];
	public $replace = [
	   ['search'=>'秒拍', 'replace'=>'短视频'], 
	   ['search'=>'美拍', 'replace'=>'短视频'], 
	   ['search'=>'网易', 'replace'=>'短视频']
    ];
	
	
	/**
	 * @return array the validation rules.
	 */
    public function rules()
    {
        return [
            [['content', 'user_id'], 'required'],
            [['create_time', 'status', 'user_id', 'parent', 'item_id', 'dig', 'is_hot'], 'integer'],
            [['client', 'uavatar'], 'string'],
            [['content'], 'filter', 'filter'=>'trim'],
            [['content'], 'string', 'min'=>4, 'max'=>1024],
            [['uname'], 'string', 'min'=>4, 'max'=>40],
            [['site'], 'string', 'max'=>40],
            [['item_type'], 'string', 'max'=>255]
        ];
    }
    
    public function valid(){
        if(strlen($this->uname) > 40 || strlen($this->uname) < 2){
        	$this->addError('用户名长度不符合要求');
        	return false;
        }
        if(strlen($this->content) < 2 || strlen($this->content) > 1024){
            $this->addError('内容长度不符合要求');
        	return false;
        }
        foreach($this->filter as $filter){
            if(strstr($this->content, $filter)){
                $this->addErrors(['包含过滤词' . $filter]);
            	return false;
            }
        }
        /* if(!preg_match('/^[\x{4e00}-\x{9fa5}A-Z0-9-a-z_-]+$/u', $this->content)){
            $this->addErrors([$this->content . '字符超出范围']);
            return false;
        } */
            
        return true;
    }
    
    public function replace($content){
        $content = strip_tags($content);
    	foreach($this->replace as $rep){
    		$content = str_replace($rep['search'], $rep['replace'], $content);
    	}
    	
    	return $content;
    }
	
	public function save(){
	    if(!$this->valid()){
	    	return false;
	    }
		$comment = CommentOrigin::findOne(['content'=>$this->content, 'item_id'=>$this->item_id, 'item_type'=>$this->item_type]);
		if(!empty($comment)){
		    $this->addErrors(['重复评论']);
			return false;
		}
	    $user = UserSearch::findOne(['username'=>$this->uname]);
	    if(empty($user)){
	        $user = new UserSearch();
	        $user->username = $this->uname;
	        $user->client_id = $this->client;
	        $user->status = User::STATUS_ACTIVE;
	        $user->is_majia = 1;
	        $user->majia_site = $this->site;
	        //@todo 
	        $user->password_reset_token = Utility::sid(time()) . Utility::sid(rand(1000, 9999));
	        //@todo 
	        $user->auth_key = 'test';
	        //echo "\t" . $this->uavatar;
	        $imgForm = new ImageForm();
	        $imgForm->url = $this->uavatar;
	        $imgAr = $imgForm->save();
	        if(!$imgAr){
	            $this->addErrors($imgForm->getErrors());
	            return false;
	        }
	        $user->avatar = $imgAr->id;
	        if(!$user->save()){
	        	$this->addErrors($user->getErrors());
	        	return false;
	        }
	    }
        $comment = new Comment();
        $comment->content = $this->replace($this->content);//@todo 处理
        $comment->client_id = $this->client;
        $comment->status = Comment::STATUS_ACTIVE;
        $comment->user_id = $user->id;
        $comment->create_time = $this->create_time;
        $comment->item_id = $this->item_id;
        $comment->item_type = $this->item_type;
        $comment->dig = $this->dig;
        if(!$comment->save()){
        	$this->addErrors($comment->getErrors());
        	return false;
        }
        $commentOrig = new CommentOrigin();
        $commentOrig->comment_id = $comment->id;
        $commentOrig->content = $this->content;
        $commentOrig->item_id = $this->item_id;
        $commentOrig->item_type = $this->item_type;
        if(!$commentOrig->save()){
            $this->addErrors($commentOrig->getErrors());
            return false;
        }
	    
	    return true;
		
	}
}