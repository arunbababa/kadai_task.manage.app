<?php

class Model_Task extends \Model
{
    # タスクリスト取得用モデル
    public static function taskList($user_id)
    {
        return DB::select()->from('tasks')
            ->where('user_id',$user_id)
            ->execute()
            ->as_array();
    }

    # タスク追加用モデル
    public static function addTask($taskname, $category, $importance, $user_id)
    {
        DB::insert('tasks')->set(array(
            'taskname' => $taskname,
            'category' => $category,
            'importance' => $importance,
            'user_id' => $user_id, // ユーザーIDを追加
        ))->execute();
    }

    # タスク削除用モデル
    public static function deleteTask($taskname, $category, $user_id)
    {
        // タスクを、タスク名とカテゴリを指定して削除
        $affected_rows = DB::delete('tasks')
                            ->where('taskname', $taskname)
                            ->where('category', $category)
                            ->where('user_id', $user_id)
                            ->execute();
        return $affected_rows > 0;
    }

    # タスク編集用モデル
    public static function updateTask($new_taskname, $new_category, $new_importance,$pre_taskname, $user_id)
    {
        // 新たなタスク名、カテゴリ、重要度をセット
        $affected_rows = DB::update('tasks')
            ->set([
                'taskname' => $new_taskname,
                'category' => $new_category,
                'importance' => $new_importance,
            ])
            ->where('taskname', $pre_taskname)
            ->where('user_id', $user_id)
            ->execute();
        return $affected_rows > 0;
    }
}