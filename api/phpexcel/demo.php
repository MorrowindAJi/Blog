    define('CHARSET', 'UTF-8');
    //数据库操作
		$live_model = new LiveModel();
		$channels_data = $live_model->getChannelsInfoo(array('id'=>$data['cid']));
		if(empty($channels_data['id'])) $this->error('错误的信息');
    //数据库操作
		if($data['type']=='export'){
			//导出
			$cid = $data['cid'];
			$expTableData = $studentList = $live_model->lookRoomUserList($cid,'','all');
	        $data = array();
	        $expCellName = $header = array(
//	                array('id' , 'ID'),
	                array('user_id' , '用户ID'),
	                array('cid' , '房间ID'),
	                array('company_id' , '公司ID'),
	                array('company' , '公司名称'),
	                array('roomid' ,'聊天室ID'),
	                array('mobile' , '手机号码'),
	                array('email' , '电子邮箱'),
	                array('name' , '姓名'),
	                array('nickname' , '昵称'),
	                array('addtime' , '添加时间'),
	                array('status' , '状态'),
	        );
	       	array_unshift($data, $header);

			//$expTitle文件名
	        //$expcellName文件列名
	        //$expTableData文件数据
	        $xlsTitle = iconv('utf-8', 'gb2312', '学员列表');//文件名称 将字符串从utf-8编码转为gb2312编码
	        $fileName = "学员列表".date('YmdHis');//设置文件名称
	        $cellNum = count($expCellName);//获取文件的列数
	        $dataNum = count($expTableData);//获取数据的条数
	        vendor("PHPExcel.PHPExcel");//导入PHPExcal类库
	        //不加\会提示notfound 原因为引入命名空间
	        $objPHPExcel = new \PHPExcel();//生成PHPExcel类实例
	        //A-AZ列
	        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
	        // 设置excel文档的属性
	        $objPHPExcel->getProperties()->setCreator("Morrowind")//设置文档属性作者
	            ->setLastModifiedBy("Morrowind")//设置最后修改人
	            ->setTitle("Microsoft Office Excel Document")//设置文档属性标题
	            ->setSubject("excel")//设置文档属性文档主题
	            ->setDescription("excel")//设置文档属性备注
	            ->setKeywords("excel")//设置文档属性关键字
	            ->setCategory("excel file");//设置文档属性类别
	        /*
	         * 合并单元格
	         * getActiveSheet(0)设置当前sheet参数为表的索引
	         * mergeCells()需要合并单元格的区间
	         */
	        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');
	        /*
	         * 分解单元格
	         */
	//        $objPHPExcel->getActiveSheet()->unmergeCells("A1:D1");
	        //设置表的名称
	        $objPHPExcel->getActiveSheet()->setTitle("学员列表");
	        // Set column widths
	        //自适应表格宽度
	        $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
	        //设置表格宽度
	        $objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(20);
	        /*
	         * 设置font
	         */
	        //设置字体大小
	//        $objPHPExcel->getActiveSheet()->getStyle("B2")->getFont()->setSize(20);
	        //字体加粗
	//        $objPHPExcel->getActiveSheet()->getStyle("B2")->getFont()->setBold(true);
	        $objPHPExcel->getActiveSheet()->getStyle("B2")->getFont()->setUnderline(\PHPExcel_Style_Font::UNDERLINE_SINGLE);
	        /*
	         * 设置在第一列显示导出时间导出时间
	         */


	//      $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
	        for($i=0;$i<$cellNum;$i++){
	            //遍历设置单元格的值 设置列名
	            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
	        }
	        // Miscellaneous glyphs, UTF-8
	        //让总循环次数小于数据条数
	        for($i=0;$i<$dataNum;$i++){
	            //让每列的数据数小于列数
	            for($j=0;$j<$cellNum;$j++){
	                //设置单元格的值
					if($expCellName[$j][0]=='addtime'){
//						echo date('Y-m-d H:i:s',$expTableData[$i][$expCellName[$j][0]]);die;
	            		$temp = date('Y-m-d H:i:s',$expTableData[$i][$expCellName[$j][0]]);
		                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $temp);
	            	}elseif($expCellName[$j][0]=='status'){
	            		$temp = $expTableData[$i][$expCellName[$j][0]]==1?'激活':'禁用';
		                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $temp);
	            	}else{
		                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
	            	}
	            }
	        }
	        header('pragma:public');
	        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
	        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
	        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	        $objWriter->save('php://output');
	        exit;
		}elseif($data['type']=='import'){
			//Import导入
//			if (1) {
			if (!empty($_FILES['file'])) {
	            $file_name = $_FILES['file']['tmp_name'];
				vendor('phpexcel.PHPExcel');
				$account = new AccountLogic();
	            //文件名为文件路径和文件名的拼接字符串
	            $objReader = \PHPExcel_IOFactory::createReader('Excel5');//创建读取实例
	            $objPHPExcel = $objReader->load($file_name,$encode='utf-8');//加载文件
	            $sheet = $objPHPExcel->getSheet(0);//取得sheet(0)表
	            $highestRow = $sheet->getHighestRow(); // 取得总行数
	            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
	            for($i=2;$i<=$highestRow;$i++){
	            	$insert = '';
					$where = '';
					$user_insert = '';
	                $insert['name'] = $user_insert['user_name']= $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
	                $insert['nickname'] = $user_insert['user_nickname'] = $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
	                $insert['mobile'] = $user_insert['mobile']= $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
	                $insert['email'] = $user_insert['user_email']= $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
					$isMatched = preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $insert['email'], $matches);
					if(!$isMatched)	continue;
					$isMatched = preg_match('/^(((\\+\\d{2}-)?0\\d{2,3}-\\d{7,8})|((\\+\\d{2}-)?(\\d{2,3}-)?([1][3,4,5,7,8][0-9]\\d{8})))$/', $insert['mobile'], $matches);
					if(!$isMatched)	continue;
					//查询用户
					if(!empty(@$insert['email'])){
						$where['user_email'] = @$insert['email'];
					}elseif(!empty(@$insert['mobile'])){
						$where['mobile'] = @$insert['mobile'];
					}
					$up_user = $account->createUser($where,$user_insert);
					$where_find['cid'] = $channels_data['id'];
					$where_find['user_id'] = $up_user;
					$where_find['email'] = @$insert['email'];
					$where_find['mobile'] = @$insert['mobile'];
					$count = $live_model->countDb($where_find,'channels_student');
					if($count!=0) continue;
	                $insert['company']= $channels_data['company'];
	                $insert['roomid']= $channels_data['roomid'];
	                $insert['addtime']= time();
	                $insert['status']= 1;
					$insert['user_id'] = $up_user;
	                $insert['cid'] = $channels_data['id'];
	                $insert['company_id']= $channels_data['company_id'];
//	                $insert['user_id'] = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
//	                $insert['cid'] = $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
//	                $insert['company_id']= $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
//	                $insert['company']= $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
//	                $insert['roomid']= $objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
//	                $insert['mobile']= $objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
//	                $insert['email']= $objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
//	                $insert['name']= $objPHPExcel->getActiveSheet()->getCell("H".$i)->getValue();
//	                $insert['nickname']= $objPHPExcel->getActiveSheet()->getCell("I".$i)->getValue();
//	                $insert['addtime']= strtotime($objPHPExcel->getActiveSheet()->getCell("J".$i)->getValue())."";
//	                $insert['status']= $objPHPExcel->getActiveSheet()->getCell("K".$i)->getValue()=="激活"?1:0;
          //数据库操作
					$flag = $live_model ->addUserToRoomAll($insert);
          //数据库操作
	            }
	            $this->success('导入成功！');
	        }else{
	            $this->error("请选择上传的文件");
			}

		}else{
			$this->error('类型错误');
		}
