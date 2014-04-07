<?php

class DatasetController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column1';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update','delete','admin','view','debug','thanks', 'report'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
        // Load model
        $model=$this->loadModel($id);
        
        // Write drawing data
        $cs=Yii::app()->getClientScript();
        $cs->registerScript("data", $model->drawing, CClientScript::POS_HEAD);
        
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Displays the thank you page.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionThanks($id)
	{
        // Load model
        //$model=$this->loadModel($id);
        
        // Write drawing data
        //$cs=Yii::app()->getClientScript();
        //$cs->registerScript("data", $model->drawing, CClientScript::POS_HEAD);
        
		$this->render('thanks',array(
			'model'=>$this->loadModel($id),
		));
	}
	    
	/**
	 * Displays a debug page model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionDebug($id)
	{
		$this->render('debug',array(
           'model'=>$this->loadModel($id),
        ));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new Dataset;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Dataset']))
		{
			$model->attributes=$_POST['Dataset'];            
			if($model->save())
            {
                // Go through each buckle in post adding it to table
                if(isset($_POST['buckleArray']))
                {
	                foreach ($_POST['buckleArray'] as $buckle_type)
	                {
	                    // Create a new Buckle model
	                    $postBuckle = new Buckle;
	                    $postBuckle->dataset_id = $model->id;
	                    $postBuckle->type = $buckle_type;
	
	                    // Save it 
	                    if (!$postBuckle->save()) print_r($postBuckle->errors);
	                }
                }
                
                // Redirect to view page
				$this->redirect(array('view','id'=>$model->id));
            }
		}
        else
        {
            // New Datasets should have an autogenerated UUID
            $model->uuid = $this->uuid();
            $model->asmt_date = date("Y-m-d");
            
            // Create an empty retinal drawing
            $model->drawing = 'var doodleSet = []';
        }
        
        // Write drawing data
        $cs=Yii::app()->getClientScript();
        $cs->registerScript("data", $model->drawing, CClientScript::POS_HEAD);
        
		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);
		
		// Default follow up date is today (removed July 2013)
/*
		if (empty($model->fu_date))
		{
			$model->fu_date = date("Y-m-d");
		}
*/
		
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Dataset']))
		{
			$model->attributes=$_POST['Dataset'];
			if($model->save())
            {
                // Delete any existing buckles related to this model
                $criteria = new CDbCriteria;
                $criteria->condition='dataset_id=:dataset_id';
                $criteria->params=array(':dataset_id'=>$model->id);
                Buckle::model()->deleteAll($criteria);
                
                // Go through each buckle in post adding it to table
                if(isset($_POST['buckleArray']))
                {
                    foreach ($_POST['buckleArray'] as $buckle_type)
                    {                        
                        // Create a new Buckle model
                        $postBuckle = new Buckle;
                        $postBuckle->dataset_id = $model->id;
                        $postBuckle->type = $buckle_type;
                        
                        // Save it (***TODO*** - error checking and logging here)
                        $postBuckle->save();
                    }
                }
                
                // Redirect to view page (if not final follow up)
                //if (!$model->fu_man_complete)
                if (empty($model->fu_date))
                {
					$this->redirect(array('view','id'=>$model->id));
				}
				else
				{
					$this->redirect(array('thanks','id'=>$model->id));
				}
            }
		}

        // Write drawing data
        $cs=Yii::app()->getClientScript();
        $cs->registerScript("data", $model->drawing, CClientScript::POS_HEAD);
        
		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models associated with the current user.
	 */
	public function actionIndex()
	{
        $criteria = new CDbCriteria;
		//$criteria->compare('userId',Yii::app()->user->id);  // NO - this makes 1 = 10,11 etc
		$criteria->addColumnCondition(array('userId' => Yii::app()->user->id));
		
		$dataProvider=new CActiveDataProvider('Dataset',array('criteria'=>$criteria));
   
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Dataset('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Dataset']))
			$model->attributes=$_GET['Dataset'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Reporting functions
	 * @param integer $id the ID of the report to be run
	 */
	public function actionReport($id)
	{
		// Confine reports to user data
        $criteria = new CDbCriteria;
		$criteria->addColumnCondition(array('userId' => Yii::app()->user->id));
		
		// Empty array to store result data
		$reportArray = array();
		
		// Get model
		$model=new Dataset('search');
		
		// Define reports here
		switch ($id) {
			case 0:
				break;
			
			// Demographics
			case 1:
				$reportArray['total'] = $model->count($criteria);

				// Get average age
				$criteria->select='SUM(pt_age) as intResult';
				$res = $model->find($criteria);
				$reportArray['average_age'] = round($res->intResult/$reportArray['total'], 0);
								
				// Get minimum age
				$criteria->select='MIN(pt_age) as intResult';
				$res = $model->find($criteria);
				$reportArray['min_age'] = $res->intResult;
				
				// Get maximum age
				$criteria->select='MAX(pt_age) as intResult';
				$res = $model->find($criteria);
				$reportArray['max_age'] = $res->intResult;
    
				$criteria->addColumnCondition(array('pt_sex' => 'Male'));
				$reportArray['male'] = $model->count($criteria);
				$reportArray['male_percent'] = round(100 * $reportArray['male']/$reportArray['total'], 0);
				$reportArray['female'] = $reportArray['total'] - $reportArray['male'];
				$reportArray['female_percent'] = round(100 * $reportArray['female']/$reportArray['total'], 0);
				break;
				
			//Detachments
				case 2:
				$reportArray['total'] = $model->count($criteria);

				// Extent
				$criteria->select='SUM(op_extent_st) as intResult';
				$res = $model->find($criteria);
				$reportArray['avg_extent_st'] = round($res->intResult/$reportArray['total'], 1);
				$criteria->select='SUM(op_extent_sn) as intResult';
				$res = $model->find($criteria);
				$reportArray['avg_extent_sn'] = round($res->intResult/$reportArray['total'], 1);
				$criteria->select='SUM(op_extent_in) as intResult';
				$res = $model->find($criteria);
				$reportArray['avg_extent_in'] = round($res->intResult/$reportArray['total'], 1);
				$criteria->select='SUM(op_extent_it) as intResult';
				$res = $model->find($criteria);
				$reportArray['avg_extent_it'] = round($res->intResult/$reportArray['total'], 1);

				// Foveal status:
				$criteria->select="SUM(CASE WHEN `op_foveal_attachment` LIKE 'On' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['fovea_on'] = $res->intResult;				
				$criteria->select="SUM(CASE WHEN `op_foveal_attachment` LIKE 'Off' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['fovea_off'] = $res->intResult;		
				$criteria->select="SUM(CASE WHEN `op_foveal_attachment` LIKE 'Bisected' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['fovea_bisected'] = $res->intResult;		
								
				// Type of break:
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'Not found' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_not_found'] = $res->intResult;				
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'U tear' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_u_tear'] = $res->intResult;		
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'Round hole' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_round_hole'] = $res->intResult;		
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'Dialysis' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_dialysis'] = $res->intResult;		
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'GRT' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_grt'] = $res->intResult;				
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'Macular hole' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_macular_hole'] = $res->intResult;		
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'Outer leaf break' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_outer_leaf_break'] = $res->intResult;		
				$criteria->select="SUM(CASE WHEN `op_largest_break_type` LIKE 'Peripapillary break' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['break_type_peripapillary_break'] = $res->intResult;		
								
				// PVR
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'None' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_none'] = $res->intResult;
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'A' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_a'] = $res->intResult;
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'B' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_b'] = $res->intResult;
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'C' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_c'] = $res->intResult;				
				
				// Right and left eye
				$criteria->addColumnCondition(array('asmt_eye' => 'Right'));
				$reportArray['right'] = $model->count($criteria);
				$reportArray['right_percent'] = round(100 * $reportArray['right']/$reportArray['total'], 0);
				$reportArray['left'] = $reportArray['total'] - $reportArray['right'];
				$reportArray['left_percent'] = round(100 * $reportArray['left']/$reportArray['total'], 0);
				
				// Surgery type
/*
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'None' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_none'] = $res->intResult;
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'A' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_a'] = $res->intResult;
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'B' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_b'] = $res->intResult;
				$criteria->select="SUM(CASE WHEN `op_pvr_type` LIKE 'C' THEN 1 ELSE 0 END) AS intResult";
				$res = $model->find($criteria);
				$reportArray['pvr_c'] = $res->intResult;	
*/

								
				// Get average age
/*
				$criteria->select='SUM(pt_age) as intResult';
				$res = $model->find($criteria);
				$reportArray['average_age'] = round($res->intResult/$reportArray['total'], 0);
				error_log($reportArray['average_age']);
								
				// Get minimum age
				$criteria->select='MIN(pt_age) as intResult';
				$res = $model->find($criteria);
				$reportArray['min_age'] = $res->intResult;
				
				// Get maximum age
				$criteria->select='MAX(pt_age) as intResult';
				$res = $model->find($criteria);
				$reportArray['max_age'] = $res->intResult;
    
				$criteria->addColumnCondition(array('pt_sex' => 'Male'));
				$reportArray['male'] = $model->count($criteria);
				$reportArray['male_percent'] = round(100 * $reportArray['male']/$reportArray['total'], 0);
				$reportArray['female'] = $reportArray['total'] - $reportArray['male'];
				$reportArray['female_percent'] = round(100 * $reportArray['female']/$reportArray['total'], 0);
*/
				break;
				
			default:
				break;
		}
		
		$this->render('report',array(
			'reportId'=>$id,
			'reportArray'=>$reportArray,
		));
	}
	
	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=Dataset::model()->findByPk((int)$id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='dataset-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
