<?php

namespace common\components\queue;
use api\models\User;
use yii\base\BaseObject;
use common\models\Test;
use yii\imagine\Image;
use Yii;
use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class UploadImageAmazon extends BaseObject implements \yii\queue\JobInterface
{	

	///* * * * * /usr/bin/php /var/www/html/console/yii queue/info; /bin/sleep 10; /usr/bin/php /var/www/html/console/yii queue/info; /bin/sleep 10; /usr/bin/php /var/www/html/console/yii queue/info; /bin/sleep 10; /usr/bin/php /var/www/html/console/yii queue/info; /bin/sleep 10; /usr/bin/php /var/www/html/console/yii queue/info; /bin/sleep 10; /usr/bin/php /var/www/html/console/yii queue/info; /bin/sleep 10;


	public $file_name;
    public $catalog;
    public $path;
    
    public function execute($queue)
    {	 
        $dir = $this->path;
        /*$test = new Test();
        $test->text = ' 1='.$this->file_name.' 2='.$dir.' 3='.env('AWS_KEY');
        $test->time = time();
        $test->save();*/

        if(filesize($dir) > 150000){
            Image::getImagine()->open($dir)->save($dir, ['jpeg_quality' => 100]);
        } 

        $s3Client = new S3Client([
            'region' => 'us-east-2',
            'version' => '2006-03-01',
            'credentials' => [
                    'key' => env('AWS_KEY'),
                    'secret' => env('AWS_SECRET'),
                ],
            ]);

        $result = $s3Client->putObject(
            array(
                'Bucket'=>'pluzo',
                'Key'    => $this->catalog.$this->file_name,
                'SourceFile' => $dir,
                'ACL' => 'public-read',
                'ContentType' => 'image',
            )
        );
        unlink($dir);
    }
}

?>