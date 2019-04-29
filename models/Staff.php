<?php

namespace app\models;

use app\exceptions\LoginException;
use Yii;
use yii\web\IdentityInterface;

/**
 * Class Staff
 * @package app\models
 * @property int id
 * @property string email
 * @property string password
 * @property string phone
 * @property string name
 * @property int is_leader
 * @property int department_id
 * @property string show_index
 * @property int staff_status
 * @property int lock_status
 * @property string avatar
 * @property string access_token
 * @property int expire_time
 */
class Staff extends Base implements IdentityInterface
{

    const UNLOCK = 0;
    const LOCK = 1;

    const IS_VAILID = 1;
    const NO_VAILID = 0;
    const PASSWORD = 'commafit';

    public static $LeaderType = 1;

    public static $NoLeaderType = 0;

    public static $expire_time = 300; // 60S

    private $_user = false;


    public static function tableName()
    {
        return 't_staff';
    }

    /**
     * 验证密码
     *
     * @CreateTime 18/3/23 14:48:44
     * @Author: fangxing@likingfit.com
     */
    public function validatePassword()
    {
        if (hash_equals($this->password, $this->getUser()->password)) {
            return true;
        }
        return false;
    }

    /**
     * 登录用户
     *
     * @param array $errors
     * @return bool
     * @throws \yii\base\Exception
     * @CreateTime 18/3/31 15:14:55
     * @Author: fangxing@likingfit.com
     */
    public function login(&$errors)
    {
        $user = $this->getUser();
        if ($user === null) {
            $errors["email"] = LoginException::getReason(LoginException::ERR_EMAIL);
            return false;
        }
        if ($user->staff_status == UNAVAILABLE){
            $errors["email"] = LoginException::getReason(LoginException::INVALID_STAFF);
            return false;
        }
        if ($user->lock_status == Staff::LOCK){
            $errors["email"] = LoginException::getReason(LoginException::LOCK_STAFF);
            return false;
        }

        if ($this->validatePassword()) {
            $user->access_token = $user->access_token ?: Yii::$app->getSecurity()->generateRandomString();
            $user->expire_time = time() + 7200;
            return $user->save() && Yii::$app->user->login($user);
        }
        $errors["password"] = LoginException::getReason(LoginException::ERR_PASSWORD);
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return static|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = Staff::findByUserEmail($this->email);
        }
        return $this->_user;
    }

    public static function findByUserEmail($email)
    {

        return static::findOne(["email" => $email]);
    }

    /**
     * 根据给到的ID查询身份。
     *
     * @param string|integer $id 被查询的ID
     * @return IdentityInterface|null 通过ID匹配到的身份对象
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * 根据 token 查询身份。
     *
     * @param string $token 被查询的 token
     * @type
     * @return IdentityInterface|null 通过 token 得到的身份对象
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @return int|string 当前用户ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string 当前用户的（cookie）认证密钥(没有用到)
     */
    public function getAuthKey()
    {
        return $this->access_token;
    }

    /**
     * @param string $authKey (没有用到)
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }

    public function getProjectDirector()
    {
        return $this->hasMany(ProjectDirector::class, ['staff_id' => 'id']);
    }

    public function getStaffIdByDepartmentId($partmentId)
    {
        return self::find()
            ->select('id')
            ->where(['department_id' => $partmentId])
            ->andWhere(['staff_status' => AVAILABLE])
            ->asArray()
            ->all();
    }

    public function getCustomer()
    {
        return $this->hasMany(Customer::class, ['dock_staff_id' => 'id']);
    }

    public function getRole()
    {
        return $this->hasOne(AuthAssignment::class, ['user_id' => 'id']);
    }
}