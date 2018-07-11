##v1/api

###菜单列表

* 接口名称：获取菜单列表
* 请求方法：get
* 请求示例：/v1/menu

###返回信息
* 数据格式：json
* data参数说明：


| 元素名称 | 数据类型 | 是否非空 | 元素说明 |
| :------:| :------: | :------: |  :------: |
| id | int | 是 | 菜单唯一编码 |
| name | string | 是 | 菜单名称 |
| parent | int | 否 | 父菜单ID |
| route | string | 是 | 路由 |
| order | int | 否 | 菜单顺序 |
| children | array | 否 | 子菜单列表 |

* 成功示例：
```
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "id": "19",
            "name": "毛利润报表",
            "parent": null,
            "route": "/site/index",
            "order": "3",
            "children": [
                {
                    "id": "20",
                    "name": "销售毛利润报表",
                    "parent": "19",
                    "route": "/sell",
                    "order": null,
                    "children": []
                }
            ]
```

