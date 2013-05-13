#ifndef _GRAPH

#define _GRAPH

#include <iostream>
#include "random.h"

using namespace std;

class graph : virtual public randomStream
{
public:
	// create a undirected graph with random connections
	graph ( int = 100, double cn = 0.1, long = 0 );
	
	// destroy the graph
	~graph();
	
	// generate a greedy solution
	void greedySolution( bool [], int& );
	
	// determine if edge is present between two vertices
	bool edgePresent(int, int);

private:
	// count true items in a boolean array
	int total( bool [], int ) const;
	
        // size of N
	int		sizeN;
	
	// N x N adjacency matrix
	bool**		adjMatrix;
	
	friend ostream& operator<< ( ostream&, const graph& );
};

#endif

