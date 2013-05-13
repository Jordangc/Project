/*
*		Genetic Algorithm and K-Nearest Neighbor gene expression microarray classification
*      
*/
 #include <mpi/mpi.h>
 #include <stdio.h>
 #include <stdlib.h>
 #include <string.h>
 #include <math.h>
 #include <time.h>

 /* further implementation will make these unnecessary*/
 #define NUM_FAKE_CLASSES 2
 #define TEMP_NUM_SAMP 38
 #define TEMP_NUM_GENES 7129
 /* int max should be double max */
 #define MAX_DISTANCE 2147483647.0 
 /* we should try to optimize these RESEARCH */
 #define POP_SIZE 100
 #define GENE_SUBSET_SIZE 50
 #define NEIGHBORS_TO_COMPARE 3
 /* a tournament size of 1 is completely random */
 #define TOURNAMENT_SIZE 4
 #define CRITERIA 0.98
 #define GENERATIONS 5
 #define MUTATION_RATE 8

/**
* Get the microarray data from a tab delimited text file 
*
* @var filename         The file that contains the microarray data
* @var dataset          acts as a 2D array indexed EXPRESSION_LEVEL X SAMPLE
* 
* @todo                 Generic input, the num_of_samples and num_of_genes need to be found.
*/
void input_samples(char filename[], int dataset[], int *num_of_samples, int *num_of_genes);

/**
* Gives the fitness of an individual based on if it shares classes with its NEIGHBORS_TO_COMPARE 
* nearest neighbors.
*
* @ret                  fitness from 0 - 1
*
* @var individual       The member of pop being evaluated
* @var classes          indexed by sample number; the classes of each sample
*
* @todo                 
*/
float fitness(int pop[], int individual, int classes[], int dataset[], int num_of_samples);

/**
* Randomly generates an array of fixed-length subsets of the genes
*
* @var pop              acts as a 2D array indexed  INDIVIDUAL X GENE
*/
void generate_pop(int pop[], int num_of_genes);

/**
* Sets neighbors to the NEIGHBORS_TO_COMPARE nearest neighbors of sample_1
*
* @var eu_distance      the euclidian distances indexed as a psuedo-jagged 1D array
* @var sample_1         member whos neighbors are retrieved
* @var neighbors        NEIGHBORS_TO_COMPARE nearest neighbors of sample_1
*/
void N_N(double eu_distance[], int sample_1, int num_of_samples, int neighbors[]);

/**
* Sets eu_distances to the euclidean distances between all samples for the GENE_SUBSET_SIZE
* sub-vector of the original sample vector.
*/
void calc_distance(int pop[], int individual, int dataset[], int num_of_samples, double eu_distances[]);

/**
*	Crossover 
* @todo         IMPLEMENT!
*/
int crossover(int pop[], int classes[], int dataset[], int num_of_samples, int num_of_genes, double criteria, int gene_subset_size, int pop_size, double mutation_rate, int tournament_size);


/**
*	Mutation
* @todo         IMPLEMENT!
*/

 int main(int argc, char *argv[])
 {
   int *dataset, *pop, num_of_samples = 0, num_of_genes = 0, winner = -1, generations = 0;

    /* GET CLASS DATA OR DO CLASS DISCOVERY INSTEAD OF THE FOLLOWING */
    int classes[TEMP_NUM_SAMP], i;

  int stime;
  long ltime;

  /* get the current calendar time */
  ltime = time(NULL);
  stime = (unsigned) ltime/2;
  srand(stime);
    /*
    for (i = 0; i < TEMP_NUM_SAMP; i++){
        classes[i] = i% NUM_FAKE_CLASSES;
    } */

    classes[0] =
    classes[1] =
    classes[2] =
    classes[3] =
    classes[4] =
    classes[5] =
    classes[6] =
    classes[7] =
    classes[8] =
    classes[9] =
    classes[10] =
    classes[11] =
    classes[12] =
    classes[13] =
    classes[14] =
    classes[15] =
    classes[16] =
    classes[17] =
    classes[18] =
    classes[19] =
    classes[20] =
    classes[21] =
    classes[22] =
    classes[23] =
    classes[24] =
    classes[25] =
    classes[26] = 0;
    classes[27] =   // is 33
    classes[28] =
    classes[29] =
    classes[30] =
    classes[31] =
    classes[32] =   // is 27
    classes[33] =
    classes[34] =
    classes[35] =
    classes[36] =
    classes[37] = 1;

	dataset = malloc((TEMP_NUM_GENES) * (TEMP_NUM_SAMP) * sizeof(int)); /* 7129 genes, 38 samples. Values should be obtained from file */

    input_samples(argv[1], dataset, &num_of_samples, &num_of_genes);

	pop = malloc(POP_SIZE * GENE_SUBSET_SIZE * sizeof(int));

    while(winner == -1){
        generate_pop(pop, num_of_genes);
        while((winner = crossover(pop, classes, dataset, num_of_samples, num_of_genes, CRITERIA, GENE_SUBSET_SIZE, POP_SIZE, MUTATION_RATE, TOURNAMENT_SIZE)) == -1 && generations < 500){
            generations++;
        }
    }

  /*  for(i=0; i < GENERATIONS; i++){
        crossover(pop, classes, dataset, num_of_samples, CRITERIA);
    }*/
    printf("the winner is %d it took %d generations\n", winner, generations);
    free(dataset);
    free(pop); 

   return 0;
 }

void input_samples(char filename[], int dataset[], int *num_of_samples, int *num_of_genes){
	int tabs, i, j;

	FILE *input, *output;
	char next_char;
	fpos_t beg;

	input = fopen(filename, "r");
	/* output = fopen("output.txt", "w");*/

	if (input == NULL) {
	  fprintf(stderr, "Can't open input file.\n");
	  exit(1);
	}

	while((next_char = fgetc(input)) != '\n'){
		if(next_char == '\t') (*num_of_samples)++;		// dereference has lower precedence than increment!
	}

	*num_of_samples = (*num_of_samples - 1) / 2;

	fgetpos(input, &beg);

	while((next_char = fgetc(input)) != EOF){
		if(next_char == '\n') (*num_of_genes)++;
	}
	
	/* (*num_of_genes)++; */

	fsetpos(input, &beg);
	for(i = 0; i < *num_of_genes; i++){
		for(j = 0; j < *num_of_samples; j++){
			tabs = 0;
			while((next_char = fgetc(input)) != EOF){
				if(next_char == '\t') tabs++;
				if((tabs == 2 && (j > 0 || i ==0)) || (tabs == 4 && j == 0 && i !=0)){
					fscanf(input,"%d", &dataset[ (i * (*num_of_samples)) + j]);
					/*fprintf(output, "sample %d gene %d expression %d\n", j, i, dataset[(i * (*num_of_samples)) + j]);*/
					break;	
				}
			}
		}		
	}

	fclose(input);
	/* fclose(output); */
}

void generate_pop(int pop[], int num_of_genes){
	int i, j, k, gene_num, repeat;

	for(i = 0; i < POP_SIZE; i++){
		for(j = 0; j < GENE_SUBSET_SIZE; j++){
			do{
                repeat = 0;
				gene_num = rand() % num_of_genes;
				for(k = 0; k < j; k++){
					if(gene_num == pop[(i * GENE_SUBSET_SIZE) + k]) repeat = 1;
				}
			}while(repeat);
			pop[(i * GENE_SUBSET_SIZE) + j] = gene_num;
		}
	}
}

void N_N(double eu_distance[], int sample_1, int num_of_samples, int neighbors[])
{
	int sample_2, i, k, increment = -1, incrementor = -2;
    double distance;
	double near[NEIGHBORS_TO_COMPARE];
	for(i = 0; i < NEIGHBORS_TO_COMPARE; i++){
		near[i] = MAX_DISTANCE;
	}

	for(sample_2 = 0; sample_2 < sample_1; sample_2++){
		distance = eu_distance[(sample_2 * num_of_samples) + sample_1 + increment];
        /*printf("DEBUG first distance %f\n", distance);*/
		for(k = 0; k < NEIGHBORS_TO_COMPARE; k++){
			if(distance < near[k]){
				near[k] = distance;
				neighbors[k] = sample_2;
                break;
			}
		}
        increment += incrementor;
        incrementor--;
	}
    /*printf("DEBUG made it past first for loop\n");*/

	for(sample_2 = sample_1 + 1; sample_2 < num_of_samples; sample_2++){
		distance = eu_distance[(sample_1 * num_of_samples) + sample_2 + increment];
        /*printf("DEBUG %d distance %f\n",(sample_1 * num_of_samples) + sample_2, distance);*/
		for(k = 0; k < NEIGHBORS_TO_COMPARE; k++){
			if(distance < near[k]){
				near[k] = distance;
				neighbors[k] = sample_2;
                break;
			}
		}
	}
}


void calc_distance(int pop[], int individual, int dataset[], int num_of_samples, double eu_distances[]){

	double sum;
   /* FILE *fp;
    fp = fopen("distances.txt", "w"); */

	int i, j, k, increment = -1, incrementor = -2;

/*    fprintf(fp, "DEBUG the eu_distance size is %d\n", sizeof(eu_distances));*/

	for (i = 0; i < num_of_samples; i++){
		for (j = (i + 1); j < num_of_samples; j++){
            sum = 0.0;
			for (k = 0; k < GENE_SUBSET_SIZE; k++){
				sum += ((dataset[(pop[(individual * GENE_SUBSET_SIZE) + k] * num_of_samples) + i] - dataset[(pop[(individual * GENE_SUBSET_SIZE) + k] * num_of_samples) + j])
                      * (dataset[(pop[(individual * GENE_SUBSET_SIZE) + k] * num_of_samples) + i] - dataset[(pop[(individual * GENE_SUBSET_SIZE) + k] * num_of_samples) + j]));
               /* fprintf(fp, "DEBUG the distance is %f\n", sum);*/
			}
			eu_distances[(i * num_of_samples) + j + increment] = sqrt(sum);
            /*fprintf(fp, "DEBUG The index %d and the sum %f\n", (i * num_of_samples) + j + increment, eu_distances[(i * num_of_samples) + j + increment]); */
		}

        increment += incrementor;
        incrementor--;
       /* fprintf(fp, "DEBUG The increment %d and the incrementor %d\n", increment, incrementor); */
	}	
    /*fclose(fp);*/
}

float fitness(int pop[], int individual, int classes[], int dataset[], int num_of_samples){

	double *eu_distances;
	int neighbors[NEIGHBORS_TO_COMPARE], i, j;
	float num_matched = 0.0;
    
    int num_of_distances = (num_of_samples * (num_of_samples - 1)) / 2;   /* move outside to compute once*/

    /* printf("DEBUG%d\n", num_of_distances); */
	eu_distances = malloc(num_of_distances * sizeof(double));

	calc_distance(pop, individual, dataset, num_of_samples, eu_distances);

	for(i = 0; i < num_of_samples; i++){
		N_N(eu_distances, i, num_of_samples,neighbors);
		for(j = 0; j < NEIGHBORS_TO_COMPARE; j++){
			if(classes[i] == classes[neighbors[j]]){
				num_matched = 1 + num_matched;
			}
		}
	}

	free(eu_distances);

	return num_matched / (num_of_samples * NEIGHBORS_TO_COMPARE);
}

int crossover(int pop[], int classes[], int dataset[], int num_of_samples, int num_of_genes, double criteria, int gene_subset_size, int pop_size, double mutation_rate, int tournament_size){

    double *fitnesses, *best_fitness;
    int *contestants, *best_contestant, *temp_pop, *genes;
    int k, i, j, repeat, crossover_point;

    fitnesses = malloc(pop_size * sizeof(double));
    best_fitness = malloc(pop_size * sizeof(double));
    best_contestant = malloc(pop_size * sizeof(int));
    temp_pop = malloc(pop_size * gene_subset_size * sizeof(int));
    contestants = malloc(tournament_size * sizeof(int));
    genes = malloc(gene_subset_size * sizeof(int));

    for(i=0; i < (pop_size * gene_subset_size); i++){
        temp_pop[i] = pop[i];
    }

    for(i=0; i < pop_size; i++){
        fitnesses[i] = fitness(pop, i, classes, dataset, num_of_samples);
        if(fitnesses[i] >= criteria){
            printf("The fitness is %f\n", fitnesses[i]);
            return i;
        }
        best_fitness[i] = 0.0;
    }

    for(k=0; k < pop_size; k++){
        /*tournament selection is simpler thus we shall use it */
        for(i=0; i < tournament_size; i++){
            repeat = 1;
            while(repeat == 1){
                contestants[i] = rand() % pop_size;
                repeat = 0;
                for(j = 0; j < i; j++){
                    if(contestants[i] == contestants[j]){
                         repeat = 1;
                    }
                }
            }
            if(fitnesses[contestants[i]] > best_fitness[k]){
                best_contestant[k] =  contestants[i];
                best_fitness[k] = fitnesses[contestants[i]];
            }
        }
    }
    /*breed the best*/

    for(i=0; i < (pop_size / 2); i += 2){
        crossover_point = rand() % gene_subset_size;
        for(j=0; j < crossover_point; j++){
            pop[(i * gene_subset_size) + j]  = temp_pop[(best_contestant[i] * gene_subset_size) + j];
            pop[((i+1) * gene_subset_size) + j]  = temp_pop[(best_contestant[i + 1] * gene_subset_size) + j];
        }
        for(j=crossover_point; j < gene_subset_size; j++){
            repeat = 0;
            for(k=0; k < gene_subset_size; k++){
                if(temp_pop[(best_contestant[i] * gene_subset_size) + k] != temp_pop[(best_contestant[i + 1] * gene_subset_size) + j]){
                    repeat = 1;
                }
            }
            if (repeat == 0){
                pop[(i * gene_subset_size) + j]  = temp_pop[(best_contestant[i + 1] * gene_subset_size) + j];
                pop[((i+1) * gene_subset_size) + j]  = temp_pop[(best_contestant[i] * gene_subset_size) + j] ;                   
            }
            else{
                pop[(i * gene_subset_size) + j]  = temp_pop[(best_contestant[i] * gene_subset_size) + j];
                pop[((i+1) * gene_subset_size) + j]  = temp_pop[(best_contestant[i + 1] * gene_subset_size) + j];
            }
        }
    }

    if((rand() % (pop_size * gene_subset_size)) < (pop_size * gene_subset_size * mutation_rate)){
        pop[rand() % (pop_size * gene_subset_size)] = rand() % num_of_genes;
        pop[rand() % (pop_size * gene_subset_size)] = rand() % num_of_genes;
    }

 /*
    for(i=0; i < pop_size; i++){
         printf("%d is %d fitness: %f genes: ", i, best_contestant[i], best_fitness[i]); 
        for(j=0; j < gene_subset_size; j++){
            printf(" %d, ", temp_pop[best_contestant[i] * gene_subset_size + j]);
        } 
        printf("\n");
    }
 */

    free(fitnesses);
    free(best_fitness);
    free(best_contestant);
    free(temp_pop);
    free(contestants);
    free(genes);

    return -1;
}

