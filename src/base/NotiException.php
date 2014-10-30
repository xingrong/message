<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

/**
    * @brief    NotiException类继承自Exception类，主要功能是通知中心的异常处理
 */
class NotiException extends Exception {

    /**
        * @brief    getInformation 获取通知中心异常信息
        *
        * @return   array('status'=>,'msg'=>)
     */
    public function getInformation() {
        $Information = array('status'=>strval($this->getCode()),'msg'=>$this->getMessage());
        return $Information;
    }
}
?>
