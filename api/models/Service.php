<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "service".
 *
 * @property int $id
 * @property float|null $price
 * @property int|null $during
 * @property string|null $name
 * @property string|null $discont
 * @property string|null $description
 * @property string|null $count
 * @property int|null $type
 */
class Service extends \yii\db\ActiveRecord
{   
    const BOOST = 1;
    const SUPER_LIKE = 2;
    const REMIND = 3;
    const PREMIUM = 4;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'service';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price'], 'number'],
            [['during', 'type'], 'integer'],
            [['name', 'discont', 'description', 'count'], 'string', 'max' => 255],
        ];
    }

    public static function getService(){
        return [
            'boost'=>Service::find()->where(['type'=>self::BOOST])->all(),
            'super_like'=>Service::find()->where(['type'=>self::SUPER_LIKE])->all(),
            'remind'=>Service::find()->where(['type'=>self::REMIND])->all(),
            'premium'=>Service::find()->where(['type'=>self::PREMIUM])->all(),
        ];
    }

    public function fields()
    {
        return [
            'id' => 'id',
            'price' => 'price',
            'name' => 'name',
            'description' => 'description',
            'discont' => 'discont',
            'count' => 'count',
            //'type' => 'type',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'price' => 'Price',
            'during' => 'During',
            'name' => 'Name',
            'discont' => 'Discont',
            'description' => 'Description',
            'count' => 'Count',
            'type' => 'Type',
        ];
    }
}
