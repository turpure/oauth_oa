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
| /brand-country | 品牌国家 | get | array |
| /brand-category | 品牌类别 | get | array |


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
		"plat": [],
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
#### introduce 
* 接口名称：推荐人毛利报表
* 请求示例：v1/report/introduce
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": ["朱晶晶"],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```
### 费用导入
#### sales-dead-fee
* 接口名称：销售死库费用
* 请求示例：v1/upload/sales-dead-fee
* 请求方法：post
* 请求格式：form-data
* 请求参数：file : example.xlsx

### 汇率设置
#### get exchange
* 接口名称：查看美元汇率
* 请求示例：v1/upload/exchange
* 请求方法：get

#### update exchange
* 接口名称：更新美元汇率
* 请求示例：v1/upload/exchange
* 请求方法：post
* 请求参数：
```
{
	"condition": {
		"devRate": "",
		"saleRate": ""
	}
}
```
#### account 
* 接口名称：eBay工具获取账号
* 请求示例：v1/tool/account
* 请求方法： post
* 请求参数： （说明：eBay 获取eBay账号，Wish 获取Wish账号，SMT 获取SMT账号）

```
{
    "condition": {
        "type": "eBay"
    }
}
```

#### site 
* 接口名称：eBay工具获取账号站点
* 请求示例：v1/tool/site
* 请求方法： post
* 请求参数： 无


#### size 
* 接口名称：销售工具获取产品码号
* 请求示例：v1/tool/size
* 请求方法： post
* 请求参数： 无

#### color 
* 接口名称：销售工具获取产品颜色
* 请求示例：v1/tool/color
* 请求方法： post
* 请求参数： 无


#### ebay-template 
* 接口名称：eBay工具获取（下载）商品模板
* 请求示例：v1/tool/ebay-template
* 请求方法： post
* 请求参数： （说明：suffix 多选账号数组；goodsCode 多个商品编码，用逗号隔开）

```
{
    "condition": {
            "suffix": [],
            "goodsCode": ""
        }
}
```


#### ebaysku
* 接口名称：eBay工具获取商品SKU列表
* 请求示例：v1/tool/ebaysku
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
            "suffix": "showtime688",
            "goodsCode": "6C0046",
            "Site": "美国",
            "Cat1": "女人世界",
            "Cat2": "内衣",
            "price": "22",
            "shipping1": "5",
            "shipping2": "5"
        }
}
```


#### ebaysku-template
* 接口名称：eBay工具获取（下载）商品SKU模板
* 请求示例：v1/tool/ebaysku-template
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
    	"setting": {
            "suffix": "showtime688",
            "goodsCode": "6C0046",
            "Site": "美国",
            "Cat1": "女人世界",
            "Cat2": "内衣",
            "price": "22",
            "shipping1": "5",
            "shipping2": "5"
        },
        "contents": {
            "remark": [
                "abc",
                "edffewfaewf"
            ],
            "SKU": [
                "6C004601",
                "6C004602"
            ],
            "Quantity": [
                20,
                20
            ],
            "StartPrice": [
                0,
                0
            ],
            "PictureURL": [
                "http://121.196.233.153/images/6C004601.jpg",
                "http://121.196.233.153/images/6C004602.jpg"
            ],
            "Color": [
                "红色",
                "粉色"
            ],
            "Size": [
                "",
                ""
            ],
            "pro1": [
                "Red",
                "Pink"
            ],
            "pro2": [
                "",
                ""
            ],
            "EAN": [
                "Does not apply",
                "Does not apply"
            ],
            "UPC": [
                "Does not apply",
                "Does not apply"
            ]
        }
    }
}
```

#### Smtsku
* 接口名称：SMT工具获取商品SKU列表
* 请求示例：v1/tool/smtsku
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
            "suffix": "aliexpress_SMT-19",
            "goodsCode": "6A0895",
            "price": "22"
        }
}
```

#### smtsku-template
* 接口名称：SMT工具获取（下载）商品SKU模板
* 请求示例：v1/tool/smtsku-template
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
        "contents": {
            "SKU": [
                "6C004601",
                "6C004602"
            ],
            "quantity": [
                20,
                20
            ],
            "price": [
                0,
                0
            ],
            "pic_url": [
                "http://121.196.233.153/images/6C004601.jpg",
                "http://121.196.233.153/images/6C004602.jpg"
            ],
            "property1": [
                "红色",
                "粉色"
            ],
            "property2": [
                "",
                ""
            ],
            "varition1": [
                "Red",
                "Pink"
            ],
            "varition2": [
                "",
                ""
            ],
            "name1": [
                "Does not apply",
                "Does not apply"
            ]
        }
    }
}
```

#### wishsku
* 接口名称：Wish工具获取商品SKU列表
* 请求示例：v1/tool/wishsku
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
            "suffix": "wish_01-eshop",
            "goodsCode": "S161",
            "price": "22",
            "msrp": "22",
            "shipping": "22"
        }
}
```


#### wishsku-template
* 接口名称：Wish工具获取（下载）商品SKU模板
* 请求示例：v1/tool/wishsku-template
* 请求方法： post
* 请求参数：

```
{
    "condition": {
        "contents": {
            "SKU": [
                "6C004601",
                "6C004602"
            ],
            "variation1": [
                1,
                2
            ],
            "variation2": [
                "黄色",
                "粉色"
            ],
           
            "pic_url": [
                "http://121.196.233.153/images/6C004601.jpg",
                "http://121.196.233.153/images/6C004602.jpg"
            ],
            "property2": [
                "黄色",
                "粉色"
            ],
            "property1": [
                1,
                2
            ],
            "quantity": [
                1000,
                1000
            ],
            "price": [
                222,
                222
            ],
            "msrp": [
                111,
                111
            ],
            "shipping": [
                333,
                333
            ],
            "shippingTime": [
               "7-21",
                "7-21"
            ]
        }
    }
}
```
### v1/data-center 数据中心

#### 缺货分析
* 接口名称：缺货分析
* 请求方法： get
* 请求示例：v1/data-center/out-of-stock-info 

### v1/tiny-tool UR小工具

#### 物流网址查询
* 接口名称：物流网址查询
* 请求方法：get
* 请求示例：v1/tiny-tool/express

#### 品牌列表
* 接口名称：品牌列表
* 请求方法：post
* 请求示例：v1/tiny-tool/brand
* 请求参数：
```json
{
	"condition": {
		"brand": "",
		"country": "美国",
		"category":"服装",
		"start":1,
		"limit":10
	}
}
```

