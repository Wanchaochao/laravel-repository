# API 列表

[TOC]

检索model和集合

## first($conditions, $columns = [])
## firstOrFail($conditions, $columns = [])
## get($conditions, $columns = [])
## pluck($conditions, $column, $key = null)

统计查询

## count($conditions, $column = '*')
## max($conditions, $column)
## min($conditions, $column)
## avg($conditions, $column)
## sum($conditions, $column)

数据递增递减

## increment($conditions, $column, $amount = 1, $extra = []) 
按查询条件指定字段递增指定值(默认递增1)

## decrement($conditions, $column, $amount = 1, $extra = []) 
按查询条件指定字段递减指定值(默认递减1)

## insert(array $values) 
新增数据
## insertGetId(array $values, $sequence = null) 
新增数据获取新增ID
## firstOrCreate(array $attributes, array $value = []) 
查询数据没有就创建
## firstOrNew(array $attributes, array $value = []) 
查询数据没有就实例化
## updateOrCreate(array $attributes, array $value = []) 
查询修改没有就创建
## updateOrInsert(array $attributes, array $values = []) 
查询修改没有就实例化

