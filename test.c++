class stree_root{
private:
	string name;
	bool check[10];
	stree** leafs;
public:
	stree_root()
	{
		child_now=0;
		memset(check,0,sizeof(check));
		leafs=new stree[10];
	}
	void set_leafs(stree,int index){};
	void set_name(string s)
	{
		name=s;
	}
};
void stree_root::set_leafs(stree st,int index)
{
	leafs[index]=&st;
}