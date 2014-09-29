<?php
/**
 * S3 にアクセスするライブラリ.
 *
 * @author Suzuki Koichi <sk8-mr51@bellks.com>
 * @copyright Copyright (c) 2014 Suzuki Koichi
 * @license see LICENSE
 **/
class Service_Aws_S3
{
    const ACL_PRIVATE = 'private';
    const ACL_PUBLIC_READ = 'public-read';
    const ACL_PUBLIC_READ_WRITE = 'public-read-write';
    const ACL_AUTHENTICATED_READ = 'authenticated-read';
    const ACL_BUCKET_OWNER_READ = 'bucket-owner-read';
    const ACL_BUCKET_OWNER_FULL_CONTROL = 'bucket-owner-full-control';

    public static function _init()
    {
        \Config::load('aws_profile', true);
    }

    private static function get_s3client()
    {
        $aws_account = [
            'key' => 'aws_iam_access_key', // 自分のアカウントのkeyに書き換える
            'secret' => 'aws_iam_secret', // 自分のアカウントのsecretに書き換える
        ];
        $s3client = \Aws\S3\S3Client::factory($aws_account);
        return $s3client;
    }

    /**
     * S3 の作成済み bucket のlistを返す。
     * 返り値のフォーマット
     * [
     *     0 => ['Name' => 'hogehoge', 'CreationDate' => '2014-09-25T05:25:34.000Z',],
     *     1 => ['Name' => 'fugafuga', 'CreationDate' => '2014-09-25T05:25:34.000Z',]
     * ]
     **/
    public static function list_buckets()
    {
        $s3client = self::get_s3client();
        $result = $s3client->listBuckets(); // Guzzle\Service\Resource\Model
        return $result['Buckets'];
    }

    /**
     * S3上にObjectを作成する（ファイルをアップロードする）.
     *
     * @param string ACL値はS3(このclass)の定数を使うこと
     * @param string $bucket bucket name
     * @param string $key 保存するkey…事実上のpath
     * @param mixed $body string|php stream 保存するファイルの中身
     * @param bool $is_body_file_path $bodyにfile pathを指定した場合 true
     * @return string アップロードしたonjectのurl
     **/
    public static function put_object($acl, $bucket, $key, $body, $is_body_file_path = false)
    {
        $s3client = self::get_s3client();
        $request_params = [
            'ACL'       => $acl,
            'Bucket'    => $bucket,
            'Key'       => $key,
        ];
        if ($is_body_file_path) {
            $request_params['SourceFile'] = $body;
        } else {
            $request_params['Body'] = $body;
        }
        $result = $s3client->putObject($request_params);
        $result_array = $result->getAll();
        return $result_array['ObjectURL'];
    }

    /**
     * S3上のObjectを削除する(ファイルを削除する).
     *
     * @param string $bucket bucket name
     * @param strinag $key 削除するobjectのkey
     **/
    public static function delete_object($bucket, $key)
    {
        $s3client = self::get_s3client();
        $request_params = [
            'Bucket'    => $bucket,
            'Key'       => $key,
        ];
        $s3client->deleteObject($request_params);
    }

    /**
     * S3上のObjectを複数同時に削除する(ファイルを削除する).
     *
     * @param string $bucket bucket name
     * @param array $keys 削除するobject keyの配列
     **/
    public static function delete_objects($bucket, $keys)
    {
        $s3client = self::get_s3client();
        $objects = [];
        foreach ($keys as $key) {
            $objects[] = [
                'Key' => $key,
            ];
        }
        $request_params = [
            'Bucket'    => $bucket,
            'Objects'   => $objects,
        ];
        $s3client->deleteObjects($request_params);
    }

    /**
     * S3上のObjectを取得する
     *
     * @param string $bucket bucket name
     * @param string $key 削除するobjectのkey
     **/
    public static function get_object($bucket, $key)
    {
        $s3client = self::get_s3client();
        $request_params = [
            'Bucket'    => $bucket,
            'Key'       => $key,
        ];
        $result = $s3client->getObject($request_params);

        return $result['Body'];
    }

    /**
     * S3上のObjectのURLを取得する、expiredを設定すると期限付きURLでACL privateのobjectも取得できる.
     *
     * @param string $bucket bucket name
     * @param string $key 削除するobjectのkey
     * @param string $expires +10minutes などテキスト表現
     * @return string url
     **/
    public static function get_object_url($bucket, $key, $expires = null)
    {
        $s3client = self::get_s3client();
        $result = $s3client->getObjectUrl($bucket, $key, $expires);

        return $result;
    }
}
