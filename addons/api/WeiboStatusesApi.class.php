<?php
		model( 'Atme' )->updateRecentAt( $data['body'] );
		exit(json_encode($data));
		$feedtopicDao = model('FeedTopic');
		$data = $feedtopicDao->where("topic_name like '%".$key."%' and recommend=1")->field('topic_id,topic_name')->limit(10)->findAll();
		exit( json_encode($data) );
}