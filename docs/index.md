## v1/api

### 菜单列表

* 接口名称：获取菜单列表
* 请求方法：get
* 请求示例：/v1/menu

### 返回信息
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

### 查询条件

* 接口名称：获取查询条件
* 请求示例：v1/condition
* 条件列表：

| 接口 | 名称 | 请求方法 | 返回数据 |
| :------:| :------: | :------: |  :------: |
| /department | 部门 | get | array |
| /plat | 平台 | get | array |
| /store | 仓库 | get | array |
| /member | 人员 | get | array |
| /account | 账号 | get | array |


### 报表

* 接口名称：查询报表
* 请求示例：v1/report
* 报表列表：

#### sales 
* 接口名称：销售毛利报表
* 请求示例：v1/report/sales
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"department": "",
		"plat": "",
		"member": "",
		"store": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"],
		"account": []
	}
}
```

#### develop 
* 接口名称：开发毛利报表
* 请求示例：v1/report/develop
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": "",
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

#### purchase 
* 接口名称：采购毛利报表
* 请求示例：v1/report/purchase
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": "",
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

#### possess 
* 接口名称：美工毛利报表
* 请求示例：v1/report/possess
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

#### ebay-sales 
* 接口名称：eBay销售毛利报表
* 请求示例：v1/report/ebay-sales
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

#### sales-trend 
* 接口名称：销售额走势
* 请求示例：v1/report/sales-trend
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"department": ["运营一部","运营二部","运营三部"],
		"plat": "",
		"member": [],
		"store": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"],
		"account": []
	}
}
```

#### account 
* 接口名称：账号毛利润报表
* 请求示例：v1/report/account
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"department": "",
		"plat": "",
		"member": [],
		"store": [],
		"sku": "",
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"],
		"account": []
	}
}
```

#### sales-dead-fee
* 接口名称：销售死库费用
* 请求示例：v1/upload/sales-dead-fee
* 请求方法：post
* 请求格式：form-data
* 请求参数：file : example.xlsx


